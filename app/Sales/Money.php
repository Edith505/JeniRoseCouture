<?php

declare(strict_types=1);

namespace App\Sales;

use DomainException;

final class Money
{
    public const TOTAL_MAX = 999999999999;

    public static function cents(string $value, int $digits = 8): int
    {
        if (!preg_match('/^([0-9]{1,' . $digits . '})(?:[.,]([0-9]{1,2}))?$/D', $value, $matches)) {
            throw new DomainException('Indiquez un montant positif avec au maximum deux décimales.');
        }

        return (int) $matches[1] * 100 + (int) str_pad($matches[2] ?? '', 2, '0');
    }

    public static function decimal(int $cents): string
    {
        return intdiv($cents, 100) . '.' . str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
    }

    public static function label(string $decimal): string
    {
        [$euros, $centimes] = explode('.', $decimal . '.00', 3);
        $grouped = preg_replace('/\B(?=(\d{3})+(?!\d))/', "\u{202F}", $euros);

        return $grouped . ',' . $centimes . ' €';
    }
}
