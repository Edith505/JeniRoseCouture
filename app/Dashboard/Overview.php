<?php

declare(strict_types=1);

namespace App\Dashboard;

use PDO;
use Throwable;

final class Overview
{
    public function __construct(private readonly PDO $db)
    {
    }

    public function load(Period $period): array
    {
        $this->db->beginTransaction();
        try {
            $activity = $this->activity($period);
            $summary = [
                'scheduled' => (int) $this->query("SELECT COUNT(*) FROM appointments a
                    JOIN availability_rules sl ON sl.id = a.slot_id
                    WHERE a.status = 'confirmed' AND sl.starts_utc >= ? AND sl.starts_utc < ?", $period->bounds())->fetchColumn(),
                'manual' => array_sum(array_column($activity, 'appointments')),
                'quotes' => array_sum(array_column($activity, 'quotes')),
                'pending' => (int) $this->query("SELECT
                    (SELECT COUNT(*) FROM appointments WHERE status = 'pending' AND created_at >= ? AND created_at < ?) +
                    (SELECT COUNT(*) FROM quotes WHERE status IN ('new','review') AND created_at >= ? AND created_at < ?)",
                    [...$period->bounds(), ...$period->bounds()])->fetchColumn(),
            ];
            $popular = $this->query('SELECT s.name, COUNT(*) AS total FROM (
                    SELECT service_id FROM appointments WHERE created_at >= ? AND created_at < ?
                    UNION ALL
                    SELECT service_id FROM quotes WHERE created_at >= ? AND created_at < ?
                ) interest JOIN services s ON s.id = interest.service_id
                GROUP BY s.id, s.name ORDER BY total DESC, s.name LIMIT 5',
                [...$period->bounds(), ...$period->bounds()])->fetchAll();
            $upcoming = $this->db->query("SELECT a.id, a.status, a.contact_name, s.name AS service_name,
                    sl.starts_utc, sl.location
                FROM appointments a JOIN availability_rules sl ON sl.id = a.slot_id
                JOIN services s ON s.id = a.service_id
                WHERE a.status IN ('confirmed','proposed') AND sl.starts_utc >= UTC_TIMESTAMP()
                ORDER BY sl.starts_utc, a.id LIMIT 6")->fetchAll();
            $this->db->commit();

            return ['summary' => $summary, 'activity' => $activity, 'popular' => $popular, 'upcoming' => $upcoming];
        } catch (Throwable $error) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $error;
        }
    }

    private function activity(Period $period): array
    {
        $buckets = $period->buckets();
        $intervals = implode(' UNION ALL ', array_fill(0, count($buckets),
            'SELECT CAST(? AS UNSIGNED) AS position, CAST(? AS DATETIME) AS from_utc, CAST(? AS DATETIME) AS until_utc'));
        $bindings = [];
        foreach ($buckets as $index => [,$from, $until]) {
            array_push($bindings, $index, $from, $until);
        }

        $result = $this->query("WITH periods AS ($intervals)
            SELECT p.position,
                (SELECT COUNT(*) FROM appointments a WHERE a.mode = 'request'
                    AND a.created_at >= p.from_utc AND a.created_at < p.until_utc) AS appointments,
                (SELECT COUNT(*) FROM quotes q WHERE q.created_at >= p.from_utc AND q.created_at < p.until_utc) AS quotes
            FROM periods p ORDER BY p.position", $bindings)->fetchAll();

        return array_map(static fn (array $row): array => [
            'label' => $buckets[(int) $row['position']][0],
            'appointments' => (int) $row['appointments'],
            'quotes' => (int) $row['quotes'],
        ], $result);
    }

    private function query(string $sql, array $bindings): \PDOStatement
    {
        $query = $this->db->prepare($sql);
        $query->execute($bindings);

        return $query;
    }
}
