<?php

declare(strict_types=1);

namespace App\Requests;

use App\Sales\Sales;
use DateTimeZone;
use DomainException;
use PDO;
use Throwable;

final class ClientRequests
{
    public const APPOINTMENT_STATES = ['pending' => 'En attente', 'proposed' => 'Créneau proposé',
        'confirmed' => 'Confirmé', 'declined' => 'Refusé', 'cancelled' => 'Annulé'];

    public const QUOTE_STATES = ['new' => 'Nouveau', 'review' => 'En étude', 'sent' => 'Devis envoyé',
        'accepted' => 'Accepté', 'declined' => 'Refusé', 'expired' => 'Expiré'];

    private const FREE = "NOT EXISTS (
        SELECT 1 FROM appointments a JOIN availability_rules existing ON existing.id = a.slot_id
        WHERE a.status IN ('confirmed', 'proposed') AND a.id <> ?
        AND existing.resource_id = slot.resource_id
        AND existing.starts_utc < slot.ends_utc AND existing.ends_utc > slot.starts_utc
    )";

    public function __construct(private readonly PDO $db)
    {
    }

    public function service(int $id, bool $published = true): ?array
    {
        $visibility = $published ? ' AND s.is_enabled = 1 AND s.archived_at IS NULL AND c.is_enabled = 1' : '';
        $query = $this->db->prepare('SELECT s.* FROM services s JOIN service_categories c ON c.id = s.category_id WHERE s.id = ?' . $visibility);
        $query->execute([$id]);

        return $query->fetch() ?: null;
    }

    public function openings(array $service, int $except = 0, bool $admin = false, ?int $slotId = null): array
    {
        if (!$admin && ($service['duration_minutes'] === null || $service['service_mode'] === 'confirm')) {
            return [];
        }

        $sql = 'SELECT slot.*, r.name AS resource_name FROM availability_rules slot
            JOIN booking_resources r ON r.id = slot.resource_id
            WHERE slot.enabled = 1 AND slot.starts_utc > UTC_TIMESTAMP()
              AND TIMESTAMPDIFF(MINUTE, slot.starts_utc, slot.ends_utc) >= ? AND ' . self::FREE;
        $params = [(int) $service['duration_minutes'], $except];
        if (in_array($service['service_mode'], ['workshop', 'home'], true)) {
            $sql .= ' AND slot.mode = ?';
            $params[] = $service['service_mode'];
        }
        if ($slotId !== null) {
            $sql .= ' AND slot.id = ?';
            $params[] = $slotId;
        }

        $query = $this->db->prepare($sql . ' ORDER BY slot.starts_utc, slot.id LIMIT 80');
        $query->execute($params);

        return $query->fetchAll();
    }

    public function book(int $serviceId, ?int $slotId, bool $automatic, array $contact, string $notes, string $key): void
    {
        $this->calendarWrite(function () use ($serviceId, $slotId, $automatic, $contact, $notes, $key): void {
            if ($this->query('SELECT id FROM appointments WHERE submitted_key = ?', [$key])->fetchColumn()) {
                return;
            }
            $service = $this->service($serviceId);
            if ($service === null) {
                throw new DomainException('Cette prestation n’est plus disponible.');
            }
            if ($automatic && ($slotId === null || $this->openings($service, slotId: $slotId) === [])) {
                throw new DomainException('Ce créneau n’est plus disponible. Choisissez un autre créneau.');
            }
            $status = $automatic ? 'confirmed' : 'pending';
            $client = $this->clientLink('appointments', $contact);
            $this->query(
                'INSERT INTO appointments (service_id, slot_id, submitted_key, contact_name, contact_email, contact_phone,
                    preference, notes, mode, status' . ($client === [] ? '' : ', client_id') . ', created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?' . ($client === [] ? '' : ', ?') . ', UTC_TIMESTAMP())',
                [$serviceId, $automatic ? $slotId : null, $key, ...$contact, $notes, $automatic ? 'automatic' : 'request', $status, ...$client],
            );
            $this->event((int) $this->db->lastInsertId(), $status, $automatic ? $slotId : null, null);
        });
    }

    public function addOpening(array $input): void
    {
        $person = RequestInput::text($input, 'resource', 140, 'Personne ou équipe', true);
        $place = RequestInput::text($input, 'location', 160, 'Atelier ou zone', true);
        $mode = $input['mode'] ?? null;
        $start = RequestInput::localTime($input['start'] ?? null);
        $end = RequestInput::localTime($input['end'] ?? null);
        $duration = $end->getTimestamp() - $start->getTimestamp();
        if (!in_array($mode, ['workshop', 'home'], true) || $start->getTimestamp() <= time()
            || $duration < 300 || $duration > 43200
        ) {
            throw new DomainException('Choisissez un créneau futur de 5 minutes à 12 heures et un mode d’intervention.');
        }
        $weeks = filter_var($input['weeks'] ?? 1, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 12]]);
        if ($weeks === false) {
            throw new DomainException('Choisissez entre 1 et 12 semaines.');
        }

        $this->calendarWrite(function () use ($person, $place, $mode, $start, $end, $weeks): void {
            $this->query('INSERT INTO booking_resources (name) VALUES (?) ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)', [$person]);
            $resource = (int) $this->db->lastInsertId();
            $utc = new DateTimeZone('UTC');
            for ($week = 0; $week < $weeks; ++$week) {
                $from = $start->modify("+$week week")->setTimezone($utc)->format('Y-m-d H:i:s');
                $to = $end->modify("+$week week")->setTimezone($utc)->format('Y-m-d H:i:s');
                if ($this->query('SELECT 1 FROM availability_rules WHERE resource_id = ? AND enabled = 1
                    AND starts_utc < ? AND ends_utc > ? LIMIT 1', [$resource, $to, $from])->fetchColumn()) {
                    throw new DomainException('Un créneau de cette personne recouvre déjà un des horaires demandés.');
                }
                $this->query('INSERT INTO availability_rules (resource_id, location, mode, starts_utc, ends_utc) VALUES (?, ?, ?, ?, ?)',
                    [$resource, $place, $mode, $from, $to]);
            }
        });
    }

    public function closeOpening(int $id): void
    {
        $this->calendarWrite(fn () => $this->query('UPDATE availability_rules SET enabled = 0 WHERE id = ?', [$id]));
    }

    public function closeDay(string $day, string $resource): void
    {
        $local = RequestInput::localTime($day . 'T00:00');
        $utc = new DateTimeZone('UTC');
        $from = $local->setTimezone($utc)->format('Y-m-d H:i:s');
        $until = $local->modify('+1 day')->setTimezone($utc)->format('Y-m-d H:i:s');

        $this->calendarWrite(fn () => $this->query(
            "UPDATE availability_rules slot JOIN booking_resources r ON r.id = slot.resource_id
            SET slot.enabled = 0 WHERE slot.starts_utc < ? AND slot.ends_utc > ? AND (? = '' OR r.name = ?)",
            [$until, $from, $resource, $resource],
        ));
    }

    public function updateAppointment(int $id, string $status, ?int $slot, string $notes, int $actor): void
    {
        if (!isset(self::APPOINTMENT_STATES[$status])) {
            throw new DomainException('Choisissez un statut valide.');
        }

        $this->calendarWrite(function () use ($id, $status, $slot, $notes, $actor): void {
            $record = $this->appointment($id);
            if ($record === null) {
                throw new DomainException('Rendez-vous introuvable.');
            }
            if (in_array($status, ['confirmed', 'proposed'], true)) {
                $service = $this->service((int) $record['service_id'], false);
                $same = $record['slot_id'] !== null && (int) $record['slot_id'] === $slot
                    && in_array($record['status'], ['proposed', 'confirmed'], true);
                if (!$same && ($slot === null || $service === null || $this->openings($service, $id, true, $slot) === [])) {
                    throw new DomainException('Choisissez un créneau disponible pour proposer ou confirmer le rendez-vous.');
                }
            } else {
                $slot = null;
            }

            $this->query('UPDATE appointments SET status = ?, slot_id = ?, admin_notes = ? WHERE id = ?', [$status, $slot, $notes, $id]);
            $this->event($id, $status, $slot, $actor);
        });
    }

    public function submitQuote(int $service, array $contact, string $method, string $notes, array $photos, string $key): void
    {
        $this->transaction(function () use ($service, $contact, $method, $notes, $photos, $key): void {
            if ($this->service($service) === null) {
                throw new DomainException('Cette prestation n’est plus disponible.');
            }

            if ($this->query('SELECT id FROM quotes WHERE submitted_key = ?', [$key])->fetchColumn()) {
                return;
            }
            $client = $this->clientLink('quotes', $contact);
            $upsert = $this->query('INSERT INTO quotes (service_id, submitted_key, contact_name, contact_email, contact_phone,
                preference, contact_method, notes' . ($client === [] ? '' : ', client_id') . ', created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?' . ($client === [] ? '' : ', ?') . ', UTC_TIMESTAMP())
                ON DUPLICATE KEY UPDATE id = quotes.id', [$service, $key, ...$contact, $method, $notes, ...$client]);

            if ($upsert->rowCount() !== 1) {
                return;
            }

            $id = (int) $this->db->lastInsertId();
            $insert = $this->db->prepare('INSERT INTO quote_attachments (quote_id, photo_webp) VALUES (?, ?)');
            foreach ($photos as $photo) {
                $insert->bindValue(1, $id, PDO::PARAM_INT);
                $insert->bindValue(2, $photo, PDO::PARAM_LOB);
                $insert->execute();
            }
        });
    }

    public function updateQuote(int $id, string $state, ?string $amount, string $reply, string $notes): void
    {
        if (!isset(self::QUOTE_STATES[$state])) {
            throw new DomainException('Choisissez un statut valide.');
        }
        $this->query('UPDATE quotes SET status = ?, proposed_amount = ?, reply = ?, admin_notes = ? WHERE id = ?',
            [$state, $amount, $reply, $notes, $id]);
    }

    public function appointment(int $id): ?array
    {
        return $this->query('SELECT a.*, s.name AS service_name, slot.starts_utc, slot.location FROM appointments a
            JOIN services s ON s.id = a.service_id LEFT JOIN availability_rules slot ON slot.id = a.slot_id WHERE a.id = ?', [$id])->fetch() ?: null;
    }

    public function quote(int $id): ?array
    {
        return $this->query('SELECT q.*, s.name AS service_name FROM quotes q JOIN services s ON s.id = q.service_id WHERE q.id = ?', [$id])->fetch() ?: null;
    }

    public function history(int $id): array
    {
        return $this->query('SELECT e.*, slot.starts_utc, u.display_name FROM appointment_events e
            LEFT JOIN availability_rules slot ON slot.id = e.slot_id LEFT JOIN users u ON u.id = e.actor_id
            WHERE e.appointment_id = ? ORDER BY e.id DESC', [$id])->fetchAll();
    }

    public function attachments(int $id): array
    {
        return $this->query('SELECT id FROM quote_attachments WHERE quote_id = ? ORDER BY id', [$id])->fetchAll(PDO::FETCH_COLUMN);
    }

    public function photo(int $id): ?string
    {
        return $this->query('SELECT photo_webp FROM quote_attachments WHERE id = ?', [$id])->fetchColumn() ?: null;
    }

    public function appointments(string $status, string $search): array
    {
        return $this->query("SELECT a.id, a.contact_name, a.status, s.name AS service_name, slot.starts_utc FROM appointments a
            JOIN services s ON s.id = a.service_id LEFT JOIN availability_rules slot ON slot.id = a.slot_id
            WHERE (? = '' OR a.status = ?)
            AND (? = '' OR LOCATE(?, a.contact_name) > 0 OR LOCATE(?, s.name) > 0)
            ORDER BY a.id DESC LIMIT 100", [$status, $status, $search, $search, $search])->fetchAll();
    }

    public function quotes(string $status, string $search): array
    {
        return $this->query("SELECT q.id, q.contact_name, q.status, s.name AS service_name FROM quotes q
            JOIN services s ON s.id = q.service_id WHERE (? = '' OR q.status = ?)
            AND (? = '' OR LOCATE(?, q.contact_name) > 0 OR LOCATE(?, s.name) > 0)
            ORDER BY q.id DESC LIMIT 100", [$status, $status, $search, $search, $search])->fetchAll();
    }

    public function agenda(): array
    {
        return $this->db->query("SELECT a.*, r.name AS resource_name,
            EXISTS(SELECT 1 FROM appointments ap WHERE ap.slot_id = a.id AND ap.status IN ('proposed','confirmed')) AS occupied
            FROM availability_rules a
            JOIN booking_resources r ON r.id = a.resource_id WHERE a.ends_utc >= UTC_TIMESTAMP()
            ORDER BY a.starts_utc, a.id LIMIT 100")->fetchAll();
    }

    private function clientLink(string $table, array $contact): array
    {
        $column = $this->query("SELECT 1 FROM information_schema.columns
            WHERE table_schema = DATABASE() AND table_name = ? AND column_name = 'client_id'", [$table]);
        if (!$column->fetchColumn()) {
            return [];
        }

        return [(new Sales($this->db))->resolveClient($contact[0], $contact[1], $contact[2])];
    }

    private function event(int $id, string $status, ?int $slot, ?int $actor): void
    {
        $this->query('INSERT INTO appointment_events (appointment_id, status, slot_id, actor_id, occurred_at) VALUES (?, ?, ?, ?, UTC_TIMESTAMP())',
            [$id, $status, $slot, $actor]);
    }

    private function calendarWrite(callable $operation): void
    {
        $this->transaction(function () use ($operation): void {
            if (!$this->db->query('SELECT id FROM booking_calendar_guard WHERE id = 1 FOR UPDATE')->fetchColumn()) {
                throw new \RuntimeException('Le calendrier n’est pas initialisé.');
            }
            $operation();
        });
    }

    private function transaction(callable $operation): void
    {
        $this->db->beginTransaction();
        try {
            $operation();
            $this->db->commit();
        } catch (Throwable $error) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $error;
        }
    }

    private function query(string $sql, array $params): \PDOStatement
    {
        $statement = $this->db->prepare($sql);
        $statement->execute($params);

        return $statement;
    }
}
