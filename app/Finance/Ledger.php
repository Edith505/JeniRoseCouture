<?php

declare(strict_types=1);

namespace App\Finance;

use App\Dashboard\Period;
use App\Sales\Money;
use DateTimeImmutable;
use DateTimeZone;
use DomainException;
use PDO;
use Throwable;

final class Ledger
{
    public const METHODS = ['cash' => 'Espèces', 'card' => 'Carte', 'transfer' => 'Virement', 'cheque' => 'Chèque', 'other' => 'Autre'];

    public function __construct(private readonly PDO $db)
    {
    }

    public function order(int $id): ?array
    {
        return $this->query("SELECT o.id, o.client_id, o.status, o.total_amount, o.deposit_expected, c.name,
             COALESCE((SELECT SUM(IF(p.direction='refund',-p.amount,p.amount)) FROM payments p WHERE p.order_id=o.id),0.00) AS net_paid
             FROM orders o JOIN clients c ON c.id=o.client_id WHERE o.id=?", [$id])->fetch() ?: null;
    }

    public function orderChoices(): array
    {
        return $this->db->query("SELECT o.id, c.name, o.status FROM orders o JOIN clients c ON c.id=o.client_id ORDER BY o.id DESC LIMIT 150")->fetchAll();
    }

    public function payment(int $orderId, string $day, string $direction, string $method, string $amount, string $reference, int $actor, string $key): void
    {
        if (!in_array($direction, ['payment', 'refund'], true) || !isset(self::METHODS[$method])) {
            throw new DomainException('Choisissez le type d’opération et le mode de paiement.');
        }

        $this->db->beginTransaction();
        try {
            $locked = $this->query('SELECT id FROM orders WHERE id = ? FOR UPDATE', [$orderId])->fetchColumn();
            $order = $locked ? $this->order($orderId) : null;
            if ($order === null) {
                throw new DomainException('Commande introuvable.');
            }
            if ($this->query('SELECT 1 FROM payments WHERE request_key = ?', [$key])->fetchColumn()) {
                $this->db->commit();
                return;
            }

            $paid = Money::cents($order['net_paid'], 10);
            $cents = Money::cents($amount, 10);
            if ($direction === 'refund' && $cents > $paid) {
                throw new DomainException('Le remboursement dépasse les sommes encaissées pour cette commande.');
            }
            if ($direction === 'payment' && ($order['status'] === 'cancelled' || $paid + $cents > Money::cents($order['total_amount'], 10))) {
                throw new DomainException('Ce paiement dépasse le solde de la commande ou concerne une commande annulée.');
            }

            $this->query('INSERT INTO payments (order_id, request_key, booked_on, direction, method, amount, reference, actor_id)
                VALUES (?,?,?,?,?,?,?,?)', [$orderId, $key, $day, $direction, $method, $amount, $reference, $actor]);
            $this->db->commit();
        } catch (Throwable $error) {
            $this->db->rollBack();
            throw $error;
        }
    }

    public function categories(): array
    {
        return $this->db->query('SELECT id,name FROM expense_categories ORDER BY name')->fetchAll();
    }

    public function addCategory(string $name): void
    {
        $this->query('INSERT INTO expense_categories (name) VALUES (?) ON DUPLICATE KEY UPDATE id=expense_categories.id', [$name]);
    }

    public function expense(int $category, string $day, string $description, string $amount, int $actor, string $key): void
    {
        if (!$this->query('SELECT id FROM expense_categories WHERE id=?', [$category])->fetchColumn()) {
            throw new DomainException('Choisissez une catégorie existante.');
        }

        $this->query('INSERT INTO expenses (category_id, request_key, booked_on, description, amount, actor_id)
            VALUES (?,?,?,?,?,?) ON DUPLICATE KEY UPDATE id=expenses.id', [$category, $key, $day, $description, $amount, $actor]);
    }

    public function payments(Period $period, ?int $order = null): array
    {
        $args = $this->days($period);
        $sql = 'SELECT p.*, c.name FROM payments p JOIN orders o ON o.id=p.order_id JOIN clients c ON c.id=o.client_id
            WHERE p.booked_on >= ? AND p.booked_on < ?';
        if ($order !== null) {
            $sql .= ' AND p.order_id = ?';
            $args[] = $order;
        }

        return $this->query($sql . ' ORDER BY p.booked_on DESC,p.id DESC', $args)->fetchAll();
    }

    public function expenses(Period $period): array
    {
        return $this->query('SELECT e.*, c.name AS category FROM expenses e JOIN expense_categories c ON c.id=e.category_id
            WHERE e.booked_on >= ? AND e.booked_on < ? ORDER BY e.booked_on DESC,e.id DESC', $this->days($period))->fetchAll();
    }

    public function expensesByCategory(Period $period): array
    {
        return $this->query('SELECT c.name AS category, SUM(e.amount) AS amount FROM expenses e JOIN expense_categories c ON c.id=e.category_id
            WHERE e.booked_on >= ? AND e.booked_on < ? GROUP BY c.id,c.name ORDER BY amount DESC,c.name', $this->days($period))->fetchAll();
    }

    public function totals(Period $period): array
    {
        $days = $this->days($period);
        $totals = $this->query("SELECT
            (SELECT COALESCE(SUM(IF(kind='credit',-total_ht,total_ht)),0.00) FROM invoices WHERE issue_on >= ? AND issue_on < ?) AS invoiced_ht,
            (SELECT COALESCE(SUM(IF(kind='credit',-total_ttc,total_ttc)),0.00) FROM invoices WHERE issue_on >= ? AND issue_on < ?) AS invoiced_ttc,
            (SELECT COALESCE(SUM(amount),0.00) FROM payments WHERE direction='payment' AND booked_on >= ? AND booked_on < ?) AS received,
            (SELECT COALESCE(SUM(amount),0.00) FROM payments WHERE direction='refund' AND booked_on >= ? AND booked_on < ?) AS refunds,
            (SELECT COALESCE(SUM(amount),0.00) FROM expenses WHERE booked_on >= ? AND booked_on < ?) AS expenses,
            (SELECT COALESCE(SUM(GREATEST(i.total_ttc - COALESCE(p.net,0),0)),0.00) FROM invoices i
                LEFT JOIN (SELECT order_id,SUM(IF(direction='refund',-amount,amount)) AS net FROM payments GROUP BY order_id) p ON p.order_id=i.order_id
                WHERE i.kind='invoice' AND NOT EXISTS(SELECT 1 FROM invoices credit WHERE credit.reverses_id=i.id)) AS outstanding",
            [...$days, ...$days, ...$days, ...$days, ...$days])->fetch();
        $balance = Money::cents($totals['received'], 12) - Money::cents($totals['refunds'], 12) - Money::cents($totals['expenses'], 12);
        $totals['net_cash'] = ($balance < 0 ? '-' : '') . Money::decimal(abs($balance));

        return $totals;
    }

    public function activity(Period $period): array
    {
        $buckets = $period->buckets();
        $sql = implode(' UNION ALL ', array_fill(0, count($buckets),
            'SELECT CAST(? AS UNSIGNED) AS position, CAST(? AS DATE) AS from_day, CAST(? AS DATE) AS until_day'));
        $utc = new DateTimeZone('UTC');
        $paris = new DateTimeZone('Europe/Paris');
        $bindings = [];
        foreach ($buckets as $position => [,$from, $until]) {
            array_push($bindings, $position,
                (new DateTimeImmutable($from, $utc))->setTimezone($paris)->format('Y-m-d'),
                (new DateTimeImmutable($until, $utc))->setTimezone($paris)->format('Y-m-d'),
            );
        }

        $rows = $this->query("WITH dates AS ($sql)
            SELECT d.position,
                (SELECT COALESCE(SUM(IF(i.kind='credit',-i.total_ht,i.total_ht)),0.00) FROM invoices i
                    WHERE i.issue_on>=d.from_day AND i.issue_on<d.until_day) AS invoiced_ht,
                (SELECT COALESCE(SUM(IF(p.direction='refund',-p.amount,p.amount)),0.00) FROM payments p
                    WHERE p.booked_on>=d.from_day AND p.booked_on<d.until_day) AS net_received,
                (SELECT COALESCE(SUM(e.amount),0.00) FROM expenses e
                    WHERE e.booked_on>=d.from_day AND e.booked_on<d.until_day) AS expenses
            FROM dates d ORDER BY d.position", $bindings)->fetchAll();
        foreach ($rows as &$row) {
            $row['label'] = $buckets[(int) $row['position']][0];
            unset($row['position']);
        }
        unset($row);

        return $rows;
    }

    private function days(Period $period): array
    {
        return [$period->start->format('Y-m-d'), $period->end->format('Y-m-d')];
    }

    private function query(string $sql, array $values): \PDOStatement
    {
        $query = $this->db->prepare($sql);
        $query->execute($values);
        return $query;
    }
}
