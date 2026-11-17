<?php

declare(strict_types=1);

namespace App\Catalog;

use DomainException;

final class CatalogueInput
{
    public static function category(array $input): array
    {
        $name = self::text($input, 'name', 140, 'Nom', required: true);

        return [
            'slug' => self::slug($name),
            'name' => $name,
            'is_enabled' => ($input['is_enabled'] ?? '') === '1' ? 1 : 0,
            'sort_order' => self::number($input, 'sort_order', 0, 65535, 'Ordre'),
        ];
    }

    public static function service(array $input): array
    {
        $fields = self::category($input);
        $type = self::choice($input, 'price_type', Catalogue::PRICE_TYPES, 'Type de tarif');
        $price = null;

        if (!in_array($type, ['quote', 'custom'], true)) {
            $amount = self::text($input, 'price_amount', 12, 'Montant', required: true);
            $price = str_replace(',', '.', $amount);
            if (preg_match('/^\d{1,8}(?:\.\d{1,2})?$/D', $price) !== 1) {
                throw new DomainException('Saisissez un montant positif avec au maximum deux décimales.');
            }
        }

        return $fields + [
            'category_id' => self::number($input, 'category_id', 1, PHP_INT_MAX, 'Catégorie'),
            'description' => self::text($input, 'description', 2000, 'Description', required: true),
            'conditions' => self::text($input, 'conditions', 2000, 'Conditions'),
            'examples' => self::text($input, 'examples', 2000, 'Exemples'),
            'price_type' => $type,
            'price_amount' => $price,
            'price_is_demo' => ($input['price_is_demo'] ?? '') === '1' ? 1 : 0,
            'duration_minutes' => ($input['duration_minutes'] ?? '') === '' ? null
                : self::number($input, 'duration_minutes', 1, 65535, 'Durée'),
            'service_mode' => self::choice($input, 'service_mode', Catalogue::MODES, 'Mode de prestation'),
            'action_intent' => self::choice($input, 'action_intent', Catalogue::ACTIONS, 'Action prévue'),
        ];
    }

    private static function text(array $input, string $key, int $limit, string $label, bool $required = false): string
    {
        $value = $input[$key] ?? '';
        if (!is_string($value) || !mb_check_encoding($value, 'UTF-8')) {
            throw new DomainException("$label : valeur invalide.");
        }
        $value = trim(str_replace(["\r\n", "\r"], "\n", $value));
        if (($required && $value === '') || mb_strlen($value) > $limit || str_contains($value, "\0")) {
            throw new DomainException("$label : valeur manquante ou trop longue.");
        }

        return $value;
    }

    private static function choice(array $input, string $key, array $choices, string $label): string
    {
        $value = $input[$key] ?? null;
        if (!is_string($value) || !array_key_exists($value, $choices)) {
            throw new DomainException("$label : choisissez une option valide.");
        }

        return $value;
    }

    private static function number(array $input, string $key, int $min, int $max, string $label): int
    {
        $value = filter_var($input[$key] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => $min, 'max_range' => $max]]);
        if ($value === false) {
            throw new DomainException("$label : indiquez un entier entre $min et $max.");
        }

        return $value;
    }

    private static function slug(string $name): string
    {
        $ascii = transliterator_transliterate('Any-Latin; Latin-ASCII; Lower()', $name);
        $slug = trim(preg_replace('/[^a-z0-9]+/', '-', $ascii ?: ''), '-');
        if ($slug === '' || strlen($slug) > 140) {
            throw new DomainException('Choisissez un nom qui peut servir à construire une adresse Web.');
        }

        return $slug;
    }
}
