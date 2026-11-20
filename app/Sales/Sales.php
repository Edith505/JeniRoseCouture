<?php

declare(strict_types=1);

namespace App\Sales;

use App\Finance\Billing;
use DomainException;
use PDO;
use Throwable;

final class Sales
{
    public const STATES = ['received' => 'Reçue', 'in_progress' => 'En cours', 'completed' => 'Terminée',
        'delivered' => 'Livrée', 'cancelled' => 'Annulée'];

    public function __construct(private readonly PDO $db)
    {
    }

    public function resolveClient(string $name, string $email, string $phone): int
    {
        $this->query('INSERT INTO clients (name, email, phone, created_at) VALUES (?, ?, ?, UTC_TIMESTAMP())
            ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)', [$name, strtolower(trim($email)), $phone]);

        return (int) $this->db->lastInsertId();
    }

    public function clients(string $search, string $filter): array
    {
        return $this->query("SELECT id, name, email, phone, archived_at FROM clients
            WHERE (? = 'all' OR (? = 'active' AND archived_at IS NULL) OR (? = 'archived' AND archived_at IS NOT NULL))
            AND (? = '' OR LOCATE(?, name) > 0 OR LOCATE(?, email) > 0 OR LOCATE(?, phone) > 0)
            ORDER BY name, id LIMIT 100", [$filter, $filter, $filter, $search, $search, $search, $search])->fetchAll();
    }

    public function client(int $id): ?array
    {
        return $this->query('SELECT c.*, a.address_line, a.postal_code, a.city, a.country FROM clients c
            LEFT JOIN client_addresses a ON a.client_id = c.id WHERE c.id = ?', [$id])->fetch() ?: null;
    }

    public function saveClient(?int $id, array $values, array $address): int
    {
        return $this->transaction(function () use ($id, $values, $address): int {
            if ($id === null) {
                $this->query('INSERT INTO clients (name, email, phone, notes, created_at) VALUES (?, ?, ?, ?, UTC_TIMESTAMP())', $values);
                $id = (int) $this->db->lastInsertId();
            } else {
                $this->query('UPDATE clients SET name = ?, email = ?, phone = ?, notes = ? WHERE id = ?', [...$values, $id]);
            }
            $this->query('INSERT INTO client_addresses (client_id, address_line, postal_code, city, country) VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE address_line = ?, postal_code = ?, city = ?, country = ?', [$id, ...$address, ...$address]);

            return $id;
        });
    }

    public function archiveClient(int $id, bool $archived): void
    {
        $this->query('UPDATE clients SET archived_at = ' . ($archived ? 'UTC_TIMESTAMP()' : 'NULL') . ' WHERE id = ?', [$id]);
    }

    public function clientActivity(int $id): array
    {
        return [
            'appointments' => $this->query('SELECT a.id, a.status, a.created_at, s.name FROM appointments a JOIN services s ON s.id = a.service_id
                WHERE a.client_id = ? ORDER BY a.id DESC LIMIT 20', [$id])->fetchAll(),
            'quotes' => $this->query('SELECT q.id, q.status, q.created_at, s.name FROM quotes q JOIN services s ON s.id = q.service_id
                WHERE q.client_id = ? ORDER BY q.id DESC LIMIT 20', [$id])->fetchAll(),
            'orders' => $this->query('SELECT id, status, total_amount, created_at FROM orders WHERE client_id = ? ORDER BY id DESC LIMIT 20', [$id])->fetchAll(),
        ];
    }

    public function orders(string $search, string $filter): array
    {
        return $this->query("SELECT o.*, c.name AS client_name, r.name AS assignee FROM orders o JOIN clients c ON c.id = o.client_id
            LEFT JOIN booking_resources r ON r.id = o.assignee_resource_id
            WHERE (? = '' OR o.status = ?) AND (? = '' OR LOCATE(?, c.name) > 0 OR CAST(o.id AS CHAR) = ?)
            ORDER BY o.id DESC LIMIT 100", [$filter, $filter, $search, $search, $search])->fetchAll();
    }

    public function order(int $id): ?array
    {
        $order = $this->query('SELECT o.*, c.name AS client_name FROM orders o JOIN clients c ON c.id = o.client_id WHERE o.id = ?', [$id])->fetch();
        if ($order) {
            $order['items'] = $this->query('SELECT service_id, label, quantity, unit_price FROM order_items WHERE order_id = ? ORDER BY sort_order', [$id])->fetchAll();
        }

        return $order ?: null;
    }

    public function history(int $id): array
    {
        return $this->query("SELECT e.status, e.occurred_at, u.display_name,
            JSON_UNQUOTE(JSON_EXTRACT(e.snapshot, '$.total_amount')) AS total_amount
            FROM order_events e JOIN users u ON u.id=e.actor_id WHERE e.order_id=? ORDER BY e.id DESC LIMIT 20", [$id])->fetchAll();
    }

    public function orderOptions(?int $currentClient): array
    {
        return [
            'clients' => $this->query('SELECT id, name, email, archived_at FROM clients WHERE archived_at IS NULL OR id = ? ORDER BY name, id', [$currentClient])->fetchAll(),
            'resources' => $this->db->query('SELECT id,name FROM booking_resources ORDER BY name')->fetchAll(),
            'services' => $this->db->query('SELECT id, name, archived_at FROM services ORDER BY archived_at, sort_order, id')->fetchAll(),
        ];
    }

    public function saveOrder(?int $id, array $data, int $actor): int
    {
        return $this->transaction(fn (): int => $this->writeOrder($id, $data, $actor));
    }

    public function convertQuote(int $quoteId, int $actor): int
    {
        return $this->transaction(function () use ($quoteId, $actor): int {
            $quote = $this->query('SELECT q.*, s.name AS service_name FROM quotes q JOIN services s ON s.id = q.service_id
                WHERE q.id = ? FOR UPDATE', [$quoteId])->fetch();
            if (!$quote) {
                throw new DomainException('Devis introuvable.');
            }
            $existing = $this->query('SELECT id FROM orders WHERE source_quote_id = ? FOR UPDATE', [$quoteId])->fetchColumn();
            if ($existing) {
                return (int) $existing;
            }
            if ($quote['status'] !== 'accepted' || $quote['proposed_amount'] === null) {
                throw new DomainException('Le devis doit être accepté et comporter un montant pour être converti.');
            }
            $client = $quote['client_id'] === null
                ? $this->resolveClient($quote['contact_name'], $quote['contact_email'], $quote['contact_phone'])
                : (int) $quote['client_id'];
            if ($quote['client_id'] === null) {
                $this->query('UPDATE quotes SET client_id = ? WHERE id = ?', [$client, $quoteId]);
            }

            return $this->writeOrder(null, [
                'client_id' => $client, 'assignee_resource_id' => null, 'status' => 'received', 'due_on' => null,
                'notes' => '', 'deposit_expected' => null, 'items' => [[
                    'service_id' => (int) $quote['service_id'], 'label' => $quote['service_name'],
                    'quantity' => 1, 'unit_price' => $quote['proposed_amount'],
                ]],
            ], $actor, $quoteId);
        });
    }

    public function totals(array $bounds): array
    {
        return [
            'clients' => (int) $this->query('SELECT COUNT(*) FROM clients WHERE created_at >= ? AND created_at < ?', $bounds)->fetchColumn(),
            'open_orders' => (int) $this->db->query("SELECT COUNT(*) FROM orders WHERE status IN ('received','in_progress')")->fetchColumn(),
            'new_orders' => (int) $this->query('SELECT COUNT(*) FROM orders WHERE created_at >= ? AND created_at < ?', $bounds)->fetchColumn(),
        ];
    }

    private function writeOrder(?int $id, array $data, int $actor, ?int $quote = null): int
    {
        $current = $id === null ? null : $this->query('SELECT client_id, source_quote_id, total_amount FROM orders WHERE id = ? FOR UPDATE', [$id])->fetch();
        if ($id !== null && !$current) {
            throw new DomainException('Commande introuvable.');
        }
        $client = $this->query('SELECT id, archived_at FROM clients WHERE id = ? FOR SHARE', [$data['client_id']])->fetch();
        if (!$client || ($client['archived_at'] !== null && (int) ($current['client_id'] ?? 0) !== $data['client_id'])) {
            throw new DomainException('Choisissez un client actif.');
        }
        if ($current && $current['source_quote_id'] !== null && (int) $current['client_id'] !== $data['client_id']) {
            throw new DomainException('Le client d’une commande issue d’un devis ne peut pas être changé.');
        }
        if ($data['assignee_resource_id'] !== null && !$this->query('SELECT 1 FROM booking_resources WHERE id = ?', [$data['assignee_resource_id']])->fetchColumn()) {
            throw new DomainException('Choisissez un responsable existant.');
        }

        $total = 0;
        $items = [];
        foreach ($data['items'] as $item) {
            if ($item['service_id'] !== null) {
                $name = $this->query('SELECT name FROM services WHERE id = ?', [$item['service_id']])->fetchColumn();
                if (!$name) {
                    throw new DomainException('Une prestation sélectionnée est introuvable.');
                }
                $item['label'] = $item['label'] === '' ? $name : $item['label'];
            }
            if ($item['label'] === '') {
                throw new DomainException('Ajoutez un libellé aux lignes sans prestation.');
            }
            $total += $item['quantity'] * Money::cents($item['unit_price']);
            if ($total > Money::TOTAL_MAX) {
                throw new DomainException('Le total dépasse le montant pris en charge.');
            }
            $items[] = $item;
        }
        if ($data['deposit_expected'] !== null && Money::cents($data['deposit_expected'], 10) > $total) {
            throw new DomainException('L’acompte convenu ne peut pas dépasser le total.');
        }
        $amount = Money::decimal($total);
        $lockedByInvoice = $current && $this->db->query("SELECT 1 FROM information_schema.tables
            WHERE table_schema=DATABASE() AND table_name='invoices'")->fetchColumn()
            && (new Billing($this->db))->activeInvoice($id) !== null;
        if ($lockedByInvoice) {
            $saved = $this->order($id)['items'];
            foreach ($saved as &$line) {
                $line['service_id'] = $line['service_id'] === null ? null : (int) $line['service_id'];
                $line['quantity'] = (int) $line['quantity'];
            }
            unset($line);
            if ((int) $current['client_id'] !== $data['client_id'] || $saved !== $items) {
                throw new DomainException('Cette commande a une facture active. Émettez un avoir avant de modifier son client ou ses lignes.');
            }
        }
        $values = [$data['client_id'], $data['assignee_resource_id'], $data['status'], $data['due_on'], $data['notes'], $data['deposit_expected'], $amount];

        if ($id === null) {
            $this->query('INSERT INTO orders (client_id,assignee_resource_id,status,due_on,notes,deposit_expected,total_amount,
                source_quote_id,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,UTC_TIMESTAMP(),UTC_TIMESTAMP())', [...$values, $quote]);
            $id = (int) $this->db->lastInsertId();
        } else {
            $this->query('UPDATE orders SET client_id=?,assignee_resource_id=?,status=?,due_on=?,notes=?,
                deposit_expected=?,total_amount=?,updated_at=UTC_TIMESTAMP() WHERE id=?', [...$values, $id]);
            if (!$lockedByInvoice) {
                $this->query('DELETE FROM order_items WHERE order_id = ?', [$id]);
            }
        }

        $insert = $this->db->prepare('INSERT INTO order_items (order_id,service_id,label,quantity,unit_price,sort_order) VALUES (?,?,?,?,?,?)');
        foreach ($lockedByInvoice ? [] : $items as $position => $item) {
            $insert->execute([$id, $item['service_id'], $item['label'], $item['quantity'], $item['unit_price'], $position]);
        }
        $data['items'] = $items;
        $data['total_amount'] = $amount;
        $this->query('INSERT INTO order_events (order_id,actor_id,status,snapshot,occurred_at) VALUES (?,?,?,?,UTC_TIMESTAMP())',
            [$id, $actor, $data['status'], json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE)]);

        return $id;
    }

    private function transaction(callable $operation): mixed
    {
        $this->db->beginTransaction();
        try {
            $result = $operation();
            $this->db->commit();
            return $result;
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
