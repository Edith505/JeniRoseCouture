<?php

declare(strict_types=1);

namespace App\Sales;

use App\Requests\RequestInput;
use DomainException;

final class OrderInput
{
    public static function lines(array $input): array
    {
        $lines = $input['lines'] ?? [];
        if (!is_array($lines) || count($lines) > 25) {
            throw new DomainException('Une commande peut contenir au maximum 25 lignes.');
        }
        foreach ($lines as $line) {
            if (!is_array($line)) {
                throw new DomainException('Ligne de commande invalide.');
            }
        }

        return array_values($lines);
    }

    public static function data(array $input): array
    {
        $client = RequestInput::id($input['client_id'] ?? null);
        $assignee = self::optionalId($input, 'assignee', 'responsable');
        $status = $input['status'] ?? null;
        $due = RequestInput::text($input, 'due_on', 10, 'Date de livraison');
        if ($client === null || !is_string($status) || !isset(Sales::STATES[$status])) {
            throw new DomainException('Choisissez un client et un statut valides.');
        }
        if ($due !== '') {
            if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/D', $due, $parts) !== 1
                || (int) $parts[1] < 1900
                || !checkdate((int) $parts[2], (int) $parts[3], (int) $parts[1])
            ) {
                throw new DomainException('Indiquez une date de livraison valide.');
            }
        }
        $deposit = RequestInput::text($input, 'deposit', 13, 'Acompte convenu');
        $items = [];
        foreach (self::lines($input) as $line) {
            $service = self::optionalId($line, 'service_id', 'prestation');
            $label = RequestInput::text($line, 'label', 180, 'Libellé');
            $price = RequestInput::text($line, 'unit_price', 11, 'Prix unitaire');
            if ($service === null && $label === '' && $price === '') {
                continue;
            }
            $quantity = filter_var($line['quantity'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 999]]);
            if ($quantity === false || $price === '') {
                throw new DomainException('Chaque ligne doit avoir une quantité de 1 à 999 et un prix unitaire.');
            }
            $items[] = ['service_id' => $service, 'label' => $label, 'quantity' => $quantity, 'unit_price' => Money::decimal(Money::cents($price))];
        }
        if ($items === []) {
            throw new DomainException('Ajoutez au moins une ligne à la commande.');
        }

        return [
            'client_id' => $client,
            'assignee_resource_id' => $assignee,
            'status' => $status,
            'due_on' => $due === '' ? null : $due,
            'notes' => RequestInput::text($input, 'notes', 3000, 'Commentaires internes'),
            'deposit_expected' => $deposit === '' ? null : Money::decimal(Money::cents($deposit, 10)),
            'items' => $items,
        ];
    }

    private static function optionalId(array $input, string $key, string $label): ?int
    {
        $value = $input[$key] ?? '';
        $id = RequestInput::id($value);
        if ($value !== '' && $id === null) {
            throw new DomainException('Identifiant de ' . $label . ' invalide.');
        }

        return $id;
    }
}
