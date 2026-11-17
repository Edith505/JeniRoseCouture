<?php

declare(strict_types=1);

namespace App\About;

use DomainException;
use PDO;
use Throwable;

final class AboutContent
{
    public const SECTIONS = [
        'story' => 'Notre histoire',
        'education' => 'Formations et diplômes',
        'experience' => 'Expériences professionnelles',
        'skill' => 'Savoir-faire',
        'value' => 'Valeurs',
        'quote' => 'Citation',
        'photo' => 'Photographies',
    ];

    public const KINDS = [
        'education' => 'Formation',
        'experience' => 'Expérience',
        'skill' => 'Compétence',
        'value' => 'Valeur',
        'photo' => 'Photographie',
    ];

    public function __construct(private readonly PDO $db)
    {
    }

    public static function local(): bool
    {
        return ($_ENV['APP_ENV'] ?? $_SERVER['APP_ENV'] ?? '') === 'local';
    }

    public static function defaults(): array
    {
        $sections = [];
        foreach (array_keys(self::SECTIONS) as $position => $section) {
            $sections[$section] = ['visible' => true, 'order' => ($position + 1) * 10];
        }

        return [
            'display_name' => 'Jeni Rose',
            'headline' => 'Jeni Rose, créatrice passionnée',
            'story' => "Une création commence par une discussion sur le style, les matières et la façon dont la pièce sera portée. Les retouches demandent la même attention : un ourlet, une taille ou une fermeture changent l’usage d’un vêtement.\n\nNous examinons la pièce et les contraintes de la matière avant de définir le travail à réaliser. Pour un projet sur mesure, vos idées et vos mesures orientent les choix.",
            'quote' => '',
            'sections' => $sections,
        ];
    }

    public function page(string $version, bool $preview = false): ?array
    {
        $this->db->beginTransaction();
        try {
            $profile = $this->db->prepare('SELECT content, is_demo, (portrait_webp IS NOT NULL) AS has_portrait
                FROM about_profiles WHERE version = ?');
            $profile->execute([$version]);
            $row = $profile->fetch();
            if (!$row || ($row['is_demo'] && !$preview && !self::local())) {
                $this->db->commit();
                return null;
            }

            $content = self::defaults();
            $data = json_decode($row['content'], true, flags: JSON_THROW_ON_ERROR);
            $content = array_replace_recursive($content, $data);
            $content['is_demo'] = (bool) $row['is_demo'];
            $content['has_portrait'] = (bool) $row['has_portrait'];
            $sql = 'SELECT entry_key, kind, title, organisation, period, description, responsibilities, competencies,
                sort_order, visible, is_demo, archived, (image_webp IS NOT NULL) AS has_image
                FROM about_entries WHERE version = ?';
            if (!$preview) {
                $sql .= ' AND archived = 0 AND visible = 1' . (self::local() ? '' : ' AND is_demo = 0');
            }
            $entries = $this->db->prepare($sql . ' ORDER BY sort_order, entry_key');
            $entries->execute([$version]);
            $content['entries'] = $entries->fetchAll();

            $this->db->commit();

            return $content;
        } catch (Throwable $error) {
            $this->db->rollBack();
            throw $error;
        }
    }

    public function saveProfile(array $content, bool $demo, ?string $picture, bool $remove): void
    {
        $json = json_encode($content, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        $this->write(function () use ($json, $demo, $picture, $remove): void {
            $statement = $this->db->prepare("UPDATE about_profiles SET content = ?, is_demo = ? WHERE version = 'draft'");
            $statement->execute([$json, (int) $demo]);
            if ($picture !== null || $remove) {
                $statement = $this->db->prepare("UPDATE about_profiles SET portrait_webp = ? WHERE version = 'draft'");
                $statement->bindValue(1, $picture, $picture === null ? PDO::PARAM_NULL : PDO::PARAM_LOB);
                $statement->execute();
            }
        });
    }

    public function saveEntry(?string $key, array $entry, ?string $image, bool $remove): void
    {
        $this->write(function () use ($key, $entry, $image, $remove): void {
            $entryKey = $key ?? bin2hex(random_bytes(16));
            $fields = ['entry_key' => $entryKey] + $entry;
            if ($key === null) {
                $query = $this->db->prepare("INSERT INTO about_entries
                    (version, entry_key, kind, title, organisation, period, description, responsibilities, competencies,
                        sort_order, visible, is_demo)
                    VALUES ('draft', :entry_key, :kind, :title, :organisation, :period, :description,
                        :responsibilities, :competencies, :sort_order, :visible, :is_demo)");
            } else {
                $query = $this->db->prepare("UPDATE about_entries SET title = :title, organisation = :organisation,
                    period = :period, description = :description, responsibilities = :responsibilities, competencies = :competencies,
                    sort_order = :sort_order, visible = :visible, is_demo = :is_demo
                    WHERE version = 'draft' AND entry_key = :entry_key AND kind = :kind AND archived = 0");
            }
            $query->execute($fields);

            if ($image !== null || $remove) {
                $imageQuery = $this->db->prepare("UPDATE about_entries SET image_webp = ?
                    WHERE version = 'draft' AND entry_key = ? AND archived = 0");
                $imageQuery->bindValue(1, $image, $image === null ? PDO::PARAM_NULL : PDO::PARAM_LOB);
                $imageQuery->bindValue(2, $entryKey);
                $imageQuery->execute();
            }
        });
    }

    public function setArchive(string $key, bool $archive): void
    {
        $this->write(function () use ($key, $archive): void {
            $query = $this->db->prepare("UPDATE about_entries SET archived = ? WHERE version = 'draft' AND entry_key = ?");
            $query->execute([(int) $archive, $key]);
        });
    }

    public function publish(): void
    {
        $this->write(function (): void {
            if (!self::local()) {
                $demo = $this->db->query("SELECT is_demo FROM about_profiles WHERE version = 'draft'")->fetchColumn();
                $entries = $this->db->query("SELECT 1 FROM about_entries WHERE version = 'draft' AND archived = 0
                    AND visible = 1 AND is_demo = 1 LIMIT 1");
                if ($demo || $entries->fetchColumn()) {
                    throw new DomainException('Retirez les exemples de démonstration avant la publication.');
                }
            }

            $this->db->exec("DELETE FROM about_profiles WHERE version = 'published'");
            $this->db->exec("INSERT INTO about_profiles (version, content, is_demo, portrait_webp)
                SELECT 'published', content, is_demo, portrait_webp FROM about_profiles WHERE version = 'draft'");
            $this->db->exec("INSERT INTO about_entries
                (version, entry_key, kind, title, organisation, period, description, responsibilities, competencies,
                    sort_order, visible, is_demo, archived, image_webp)
                SELECT 'published', entry_key, kind, title, organisation, period, description, responsibilities, competencies,
                    sort_order, visible, is_demo, archived, image_webp
                FROM about_entries WHERE version = 'draft' AND archived = 0 AND visible = 1");
        });
    }

    public function picture(string $version, string $key, bool $preview = false): ?string
    {
        $allowDemo = $preview || self::local();
        if ($key === 'portrait') {
            $query = $this->db->prepare('SELECT portrait_webp FROM about_profiles WHERE version = ?'
                . ($allowDemo ? '' : ' AND is_demo = 0'));
            $query->execute([$version]);

            return $query->fetchColumn() ?: null;
        }

        $sql = 'SELECT e.image_webp FROM about_entries AS e JOIN about_profiles AS p ON p.version = e.version
            WHERE e.version = ? AND e.entry_key = ?';
        if (!$preview) {
            $sql .= " AND e.visible = 1 AND e.archived = 0
                AND COALESCE(JSON_UNQUOTE(JSON_EXTRACT(p.content, CONCAT('$.sections.', e.kind, '.visible'))), 'true') = 'true'";
        }
        if (!$allowDemo) {
            $sql .= ' AND p.is_demo = 0 AND e.is_demo = 0';
        }
        $query = $this->db->prepare($sql);
        $query->execute([$version, $key]);

        return $query->fetchColumn() ?: null;
    }

    private function write(callable $operation): void
    {
        $this->db->beginTransaction();
        try {
            $this->db->exec("INSERT INTO about_profiles (version, content) VALUES ('draft', JSON_OBJECT())
                ON DUPLICATE KEY UPDATE version = about_profiles.version");
            $operation();
            $this->db->commit();
        } catch (Throwable $error) {
            $this->db->rollBack();
            throw $error;
        }
    }
}
