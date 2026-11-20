<?php

declare(strict_types=1);

namespace App\Finance;

use App\Sales\Money;
use DomainException;

final class InvoiceAmounts
{
    public static function calculate(array $items, int $rate): array
    {
        if ($items === []) {
            throw new DomainException('Ajoutez des lignes à la commande avant de la facturer.');
        }

        $base = 10000 + $rate;
        $totalHt = $totalTax = $totalTtc = 0;
        $lines = [];
        foreach ($items as $item) {
            $quantity = (int) $item['quantity'];
            $unit = Money::cents($item['unit_price']);
            $gross = $unit * $quantity;
            $net = intdiv($gross * 10000 + intdiv($base, 2), $base);
            $unitHt = intdiv($unit * 100000000 + intdiv($base, 2), $base);
            $lines[] = [
                'description' => $item['label'],
                'quantity' => $quantity,
                'unit_ht' => intdiv($unitHt, 1000000) . '.' . str_pad((string) ($unitHt % 1000000), 6, '0', STR_PAD_LEFT),
                'vat_basis_points' => $rate,
                'amount_ht' => Money::decimal($net),
                'tax_amount' => Money::decimal($gross - $net),
                'amount_ttc' => Money::decimal($gross),
            ];
            $totalHt += $net;
            $totalTax += $gross - $net;
            $totalTtc += $gross;
        }

        return [
            'lines' => $lines,
            'total_ht' => Money::decimal($totalHt),
            'tax_amount' => Money::decimal($totalTax),
            'total_ttc' => Money::decimal($totalTtc),
        ];
    }

    public static function label(string $value): string
    {
        if (str_starts_with($value, '-')) {
            return '-' . Money::label(substr($value, 1));
        }

        return Money::label($value);
    }

    public static function unitLabel(string $value): string
    {
        [$whole, $fraction] = explode('.', $value);
        $fraction = str_pad(rtrim($fraction, '0'), 2, '0');

        return explode(',', Money::label($whole . '.00'), 2)[0] . ',' . $fraction . ' €';
    }
}
