<?php

declare(strict_types=1);

namespace App\Catalog;

use App\Database\Connection;
use PDO;
use PDOException;
use Throwable;

final class Catalogue
{
    public const PRICE_TYPES = [
        'fixed' => 'Prix fixe',
        'from' => 'À partir de',
        'hourly' => 'Prix horaire',
        'from_hourly' => 'Prix horaire à partir de',
        'quote' => 'Sur devis',
        'custom' => 'Tarif personnalisé',
    ];

    public const MODES = [
        'confirm' => 'Lieu à confirmer',
        'workshop' => 'En atelier',
        'home' => 'À domicile',
        'both' => 'En atelier ou à domicile',
    ];

    public const ACTIONS = [
        'information' => 'Informations',
        'appointment' => 'Rendez-vous',
        'quote' => 'Devis',
    ];

    private const SELECT = 'SELECT services.*, c.name AS category_name, c.is_enabled AS category_enabled,
        (i.service_id IS NOT NULL) AS has_upload
        FROM services JOIN service_categories AS c ON c.id = services.category_id
        LEFT JOIN service_images AS i ON i.service_id = services.id';

    public function __construct(private readonly PDO $db)
    {
    }

    public static function published(?string $slug = null): array
    {
        try {
            $db = Connection::open();
            $sql = self::SELECT . ' WHERE c.is_enabled = 1 AND services.is_enabled = 1 AND services.archived_at IS NULL';
            $query = $db->prepare($sql . ($slug === null ? '' : ' AND services.slug = ?')
                . ' ORDER BY c.sort_order, services.sort_order, services.id');
            $query->execute($slug === null ? [] : [$slug]);

            return $query->fetchAll();
        } catch (PDOException $error) {
            if (($error->errorInfo[1] ?? null) !== 1146
                || ($_ENV['APP_ENV'] ?? $_SERVER['APP_ENV'] ?? '') !== 'local'
            ) {
                throw $error;
            }
            // Keep the design preview available until the catalogue migration is applied.
            $services = DemoCatalogue::services();

            return $slug === null ? $services : array_values(array_filter(
                $services,
                static fn (array $service): bool => $service['slug'] === $slug,
            ));
        }
    }

    public function categories(): array
    {
        return $this->db->query('SELECT * FROM service_categories ORDER BY sort_order, id')->fetchAll();
    }

    public function entries(): array
    {
        return $this->db->query(self::SELECT . ' ORDER BY services.archived_at, c.sort_order, services.sort_order, services.id')->fetchAll();
    }

    public function service(int $id): ?array
    {
        $query = $this->db->prepare(self::SELECT . ' WHERE services.id = ?');
        $query->execute([$id]);

        return $query->fetch() ?: null;
    }

    public function category(int $id): ?array
    {
        $query = $this->db->prepare('SELECT * FROM service_categories WHERE id = ?');
        $query->execute([$id]);

        return $query->fetch() ?: null;
    }

    public function saveCategory(?int $id, array $fields): void
    {
        if ($id === null) {
            $query = $this->db->prepare('INSERT INTO service_categories (slug, name, is_enabled, sort_order)
                VALUES (:slug, :name, :is_enabled, :sort_order)');
        } else {
            $query = $this->db->prepare('UPDATE service_categories SET name = :name, is_enabled = :is_enabled,
                sort_order = :sort_order WHERE id = :id');
            unset($fields['slug']);
            $fields['id'] = $id;
        }
        $query->execute($fields);
    }

    public function saveService(?int $id, array $fields, ?string $newImage, bool $removeImage): void
    {
        $this->db->beginTransaction();

        try {
            if ($id === null) {
                $query = $this->db->prepare('INSERT INTO services
                    (category_id, slug, name, description, conditions, examples, price_type, price_amount,
                    price_is_demo, duration_minutes, service_mode, action_intent, is_enabled, sort_order)
                    VALUES (:category_id, :slug, :name, :description, :conditions, :examples, :price_type, :price_amount,
                    :price_is_demo, :duration_minutes, :service_mode, :action_intent, :is_enabled, :sort_order)');
                $query->execute($fields);
                $id = (int) $this->db->lastInsertId();
            } else {
                $lock = $this->db->prepare('SELECT 1 FROM services WHERE id = ? AND archived_at IS NULL FOR UPDATE');
                $lock->execute([$id]);
                if (!$lock->fetchColumn()) {
                    throw new \DomainException('La prestation est introuvable ou archivée.');
                }

                $query = $this->db->prepare('UPDATE services SET category_id = :category_id, name = :name,
                    description = :description, conditions = :conditions, examples = :examples, price_type = :price_type,
                    price_amount = :price_amount, price_is_demo = :price_is_demo, duration_minutes = :duration_minutes,
                    service_mode = :service_mode, action_intent = :action_intent, is_enabled = :is_enabled, sort_order = :sort_order
                    WHERE id = :id AND archived_at IS NULL');
                unset($fields['slug']);
                $fields['id'] = $id;
                $query->execute($fields);
            }

            if ($newImage !== null) {
                $query = $this->db->prepare('INSERT INTO service_images (service_id, image_webp) VALUES (:id, :data)
                    ON DUPLICATE KEY UPDATE image_webp = :replacement');
                $query->bindValue(':id', $id, PDO::PARAM_INT);
                $query->bindValue(':data', $newImage, PDO::PARAM_LOB);
                $query->bindValue(':replacement', $newImage, PDO::PARAM_LOB);
                $query->execute();
            } elseif ($removeImage) {
                $this->db->prepare('DELETE FROM service_images WHERE service_id = ?')->execute([$id]);
                $this->db->prepare('UPDATE services SET asset_name = NULL WHERE id = ?')->execute([$id]);
            }

            $this->db->commit();
        } catch (Throwable $error) {
            $this->db->rollBack();
            throw $error;
        }
    }

    public function archive(int $id): void
    {
        $query = $this->db->prepare('UPDATE services SET is_enabled = 0, archived_at = UTC_TIMESTAMP(6)
            WHERE id = ? AND archived_at IS NULL');
        $query->execute([$id]);
    }

    public function publicImage(int $id): ?string
    {
        $query = $this->db->prepare('SELECT i.image_webp FROM service_images AS i
            JOIN services AS s ON s.id = i.service_id JOIN service_categories AS c ON c.id = s.category_id
            WHERE s.id = ? AND s.is_enabled = 1 AND s.archived_at IS NULL AND c.is_enabled = 1');
        $query->execute([$id]);

        return $query->fetchColumn() ?: null;
    }

    public static function priceLabel(array $service): string
    {
        $type = $service['price_type'];
        if ($type === 'quote' || $type === 'custom') {
            return self::PRICE_TYPES[$type];
        }

        [$euros, $cents] = explode('.', $service['price_amount'] . '.00', 3);
        $amount = $euros . ($cents === '00' ? '' : ',' . $cents) . ' €';

        return match ($type) {
            'from' => 'À partir de ' . $amount,
            'hourly' => $amount . ' par heure',
            'from_hourly' => 'À partir de ' . $amount . ' par heure',
            default => $amount,
        };
    }

    public static function imageUrl(array $service): ?string
    {
        if ($service['has_upload']) {
            return '/index.php/prestations-image/' . (int) $service['id'];
        }

        return $service['asset_name'] === null ? null : '/assets/images/' . rawurlencode($service['asset_name']);
    }
}
