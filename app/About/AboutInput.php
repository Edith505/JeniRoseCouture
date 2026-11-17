<?php

declare(strict_types=1);

namespace App\About;

use DomainException;

final class AboutInput
{
    public static function profile(array $post): array
    {
        $sections = [];
        foreach (array_keys(AboutContent::SECTIONS) as $key) {
            $sections[$key] = [
                'visible' => ($post['section_visible'][$key] ?? '') === '1',
                'order' => self::order($post['section_order'][$key] ?? null),
            ];
        }

        return [
            'display_name' => self::text($post, 'display_name', 140, 'Nom', true),
            'headline' => self::text($post, 'headline', 180, 'Titre', true),
            'story' => self::text($post, 'story', 8000, 'Histoire'),
            'quote' => self::text($post, 'quote', 500, 'Citation'),
            'sections' => $sections,
        ];
    }

    public static function entry(array $post, string $kind): array
    {
        if (!isset(AboutContent::KINDS[$kind])) {
            throw new DomainException('Type de contenu invalide.');
        }

        $timeline = $kind === 'education' || $kind === 'experience';

        return [
            'kind' => $kind,
            'title' => self::text($post, 'title', 160, 'Intitulé', true),
            'organisation' => $timeline ? self::text($post, 'organisation', 160, 'Établissement') : '',
            'period' => $timeline ? self::text($post, 'period', 100, 'Période') : '',
            'description' => self::text($post, 'description', 4000, 'Description'),
            'responsibilities' => $kind === 'experience' ? self::text($post, 'responsibilities', 2500, 'Missions') : '',
            'competencies' => $kind === 'experience' ? self::text($post, 'competencies', 2500, 'Compétences') : '',
            'sort_order' => self::order($post['sort_order'] ?? null),
            'visible' => ($post['visible'] ?? '') === '1' ? 1 : 0,
            'is_demo' => ($post['is_demo'] ?? '') === '1' ? 1 : 0,
        ];
    }

    private static function text(array $post, string $key, int $max, string $label, bool $required = false): string
    {
        $raw = $post[$key] ?? '';
        if (!is_string($raw) || !mb_check_encoding($raw, 'UTF-8')) {
            throw new DomainException("$label : texte invalide.");
        }
        $value = trim(str_replace(["\r\n", "\r"], "\n", $raw));
        if (($required && $value === '') || mb_strlen($value) > $max || str_contains($value, "\0")) {
            throw new DomainException("$label : texte manquant ou trop long.");
        }

        return $value;
    }

    private static function order(mixed $raw): int
    {
        $order = filter_var($raw, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 65535]]);
        if ($order === false) {
            throw new DomainException('Indiquez un ordre compris entre 0 et 65535.');
        }

        return $order;
    }
}
