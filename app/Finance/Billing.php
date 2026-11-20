<?php

declare(strict_types=1);

namespace App\Finance;

use App\Dashboard\Period;
use App\Sales\Money;
use App\Sales\Sales;
use DomainException;
use PDO;
use Throwable;

final class Billing
{
    public function __construct(private readonly PDO $db)
    {
    }

    public function profile(): ?array
    {
        $result = $this->db->query('SELECT issuer FROM finance_profile WHERE id=1')->fetchColumn();

        return $result === false ? null : json_decode($result, true, flags: JSON_THROW_ON_ERROR);
    }

    public function saveProfile(array $values): void
    {
        $json = json_encode($values, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        $this->query('INSERT INTO finance_profile (id, issuer) VALUES (1,?) ON DUPLICATE KEY UPDATE issuer=?', [$json, $json]);
    }

    public function list(Period $period, ?int $order = null): array
    {
        $args = [$period->start->format('Y-m-d'), $period->end->format('Y-m-d')];
        $sql = "SELECT i.id, i.number, i.kind, i.issue_on, i.total_ht, i.tax_amount, i.total_ttc, i.order_id,
            c.name, x.number AS credited_by,
            COALESCE((SELECT SUM(IF(p.direction='refund',-p.amount,p.amount)) FROM payments p WHERE p.order_id=i.order_id),0.00) AS net_paid
            FROM invoices i JOIN orders o ON o.id=i.order_id JOIN clients c ON c.id=o.client_id
            LEFT JOIN invoices x ON x.reverses_id=i.id WHERE i.issue_on>=? AND i.issue_on<?";
        if ($order !== null) {
            $sql .= ' AND i.order_id=?';
            $args[] = $order;
        }
        return $this->query($sql . ' ORDER BY i.id DESC', $args)->fetchAll();
    }

    public function document(int $id): ?array
    {
        return $this->query("SELECT i.id,i.order_id,i.number,i.kind,i.total_ttc,i.snapshot,
            credit.number AS credited_by FROM invoices i LEFT JOIN invoices credit ON credit.reverses_id=i.id WHERE i.id=?", [$id])->fetch() ?: null;
    }

    public function pdf(int $id): ?array
    {
        return $this->query('SELECT number,pdf FROM invoices WHERE id=?', [$id])->fetch() ?: null;
    }

    public function activeInvoice(int $orderId): ?int
    {
        $id = $this->query("SELECT id FROM invoices i WHERE i.order_id=? AND i.kind='invoice'
            AND NOT EXISTS(SELECT 1 FROM invoices a WHERE a.reverses_id=i.id)", [$orderId])->fetchColumn();

        return $id === false ? null : (int) $id;
    }

    public function issue(int $orderId, array $input, int $actor): int
    {
        return $this->transaction(function () use ($orderId, $input, $actor): int {
            $order = $this->lockOrder($orderId);
            $active = $this->activeInvoice($orderId);
            if ($active !== null) {
                return $active;
            }
            if ($order['status'] === 'cancelled') {
                throw new DomainException('Une commande annulée ne peut pas être facturée.');
            }
            $issuer = $this->profile();
            if ($issuer === null) {
                throw new DomainException('Renseignez d’abord le profil de facturation.');
            }

            [$number, $date] = $this->number();
            $details = FinanceInput::invoice($input, $issuer, $date);
            $data = (new Sales($this->db))->order($orderId);
            $amounts = InvoiceAmounts::calculate($data['items'], $details['vat_rate']);
            if (Money::cents($amounts['total_ttc'], 10) !== Money::cents($order['total_amount'], 10)) {
                throw new DomainException('Le total de la commande doit correspondre à ses lignes.');
            }

            return $this->store($amounts + [
                'order_id' => $orderId, 'number' => $number, 'kind' => 'invoice', 'issue_on' => $date,
                'issuer' => $issuer, 'details' => $details, 'reference' => null, 'reason' => null, 'reverses_id' => null,
            ], $actor);
        });
    }

    public function credit(int $id, string $reason, int $actor): int
    {
        return $this->transaction(function () use ($id, $reason, $actor): int {
            $original = $this->query("SELECT order_id,kind FROM invoices WHERE id=?", [$id])->fetch();
            if (!$original || $original['kind'] !== 'invoice') {
                throw new DomainException('Sélectionnez une facture à créditer.');
            }
            $this->lockOrder((int) $original['order_id']);
            $previousCredit = $this->query('SELECT id FROM invoices WHERE reverses_id=? FOR UPDATE', [$id])->fetchColumn();
            if ($previousCredit !== false) {
                return (int) $previousCredit;
            }

            $source = $this->query('SELECT snapshot FROM invoices WHERE id=?', [$id])->fetchColumn();
            $document = json_decode($source, true, flags: JSON_THROW_ON_ERROR);
            [$number, $date] = $this->number();
            $document = array_replace($document, [
                'number' => $number, 'kind' => 'credit', 'issue_on' => $date,
                'reference' => $document['number'], 'reason' => $reason, 'reverses_id' => $id,
            ]);

            return $this->store($document, $actor);
        });
    }

    private function number(): array
    {
        $value = $this->db->query('SELECT last_number FROM invoice_sequence WHERE id=1 FOR UPDATE')->fetchColumn();
        if ($value === false) {
            throw new \RuntimeException('Valeur de numérotation introuvable.');
        }
        $today = FinanceInput::today();
        $last = $this->db->query('SELECT issue_on FROM invoices ORDER BY id DESC LIMIT 1 FOR UPDATE')->fetchColumn();
        if ($last !== false && $today < $last) {
            throw new DomainException('La date du serveur précède celle du dernier document émis.');
        }

        $next = (int) $value + 1;
        $this->query('UPDATE invoice_sequence SET last_number=? WHERE id=1', [$next]);

        return ['F-' . str_pad((string) $next, 6, '0', STR_PAD_LEFT), $today];
    }

    private function store(array $document, int $actor): int
    {
        $pdf = FinancePdf::invoice($document);
        if (!str_starts_with($pdf, '%PDF-')) {
            throw new \RuntimeException('Le document PDF est invalide.');
        }
        $statement = $this->db->prepare('INSERT INTO invoices (order_id,number,kind,reverses_id,issue_on,issue_at,total_ht,tax_amount,
            total_ttc,snapshot,actor_id,pdf) VALUES (?,?,?,?,?,UTC_TIMESTAMP(),?,?,?,?,?,?)');
        $values = [$document['order_id'], $document['number'], $document['kind'], $document['reverses_id'], $document['issue_on'],
            $document['total_ht'], $document['tax_amount'], $document['total_ttc'],
            json_encode($document, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE), $actor];
        foreach ($values as $position => $value) {
            $statement->bindValue($position + 1, $value, $value === null ? PDO::PARAM_NULL : (is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR));
        }
        $statement->bindValue(count($values) + 1, $pdf, PDO::PARAM_LOB);
        $statement->execute();
        $id = (int) $this->db->lastInsertId();

        $item = $this->db->prepare('INSERT INTO invoice_items (invoice_id,position,description,quantity,unit_ht,vat_basis_points,
            amount_ht,tax_amount,amount_ttc) VALUES (?,?,?,?,?,?,?,?,?)');
        foreach ($document['lines'] as $position => $line) {
            $item->execute([$id, $position, $line['description'], $line['quantity'], $line['unit_ht'], $line['vat_basis_points'],
                $line['amount_ht'], $line['tax_amount'], $line['amount_ttc']]);
        }
        return $id;
    }

    private function lockOrder(int $order): array
    {
        $record = $this->query('SELECT id,status,total_amount FROM orders WHERE id=? FOR UPDATE', [$order])->fetch();
        if (!$record) {
            throw new DomainException('Commande introuvable.');
        }
        return $record;
    }

    private function transaction(callable $callback): int
    {
        $this->db->beginTransaction();
        try {
            $id = $callback();
            $this->db->commit();
            return $id;
        } catch (Throwable $error) {
            $this->db->rollBack();
            throw $error;
        }
    }

    private function query(string $sql, array $values): \PDOStatement
    {
        $statement = $this->db->prepare($sql);
        $statement->execute($values);
        return $statement;
    }
}
