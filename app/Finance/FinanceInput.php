<?php

declare(strict_types=1);

namespace App\Finance;

use App\Requests\RequestInput;
use App\Sales\Money;
use DateTimeImmutable;
use DateTimeZone;
use DomainException;

final class FinanceInput
{
    public static function today(): string
    {
        return (new DateTimeImmutable('today', new DateTimeZone('Europe/Paris')))->format('Y-m-d');
    }

    public static function date(array $input, string $key, string $label): string
    {
        $value = RequestInput::text($input, $key, 10, $label, true);
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/D', $value, $parts) !== 1
            || (int) $parts[1] < 1900 || !checkdate((int) $parts[2], (int) $parts[3], (int) $parts[1])
        ) {
            throw new DomainException($label . ' : date invalide.');
        }

        return $value;
    }

    public static function postedOn(array $input): string
    {
        $day = self::date($input, 'booked_on', 'Date de l’opération');
        if ($day > self::today()) {
            throw new DomainException('La date de l’opération doit être aujourd’hui ou antérieure.');
        }

        return $day;
    }

    public static function amount(array $input): string
    {
        $value = Money::cents(RequestInput::text($input, 'amount', 13, 'Montant', true), 10);
        if ($value <= 0) {
            throw new DomainException('Saisissez un montant strictement positif.');
        }

        return Money::decimal($value);
    }

    public static function issuer(array $post): array
    {
        $values = [];
        foreach ([
            'name' => [190, 'Dénomination légale', true],
            'address' => [200, 'Adresse du siège', true],
            'postal' => [5, 'Code postal', true],
            'city' => [120, 'Ville', true],
            'siren' => [9, 'SIREN', true],
            'registration' => [200, 'Forme juridique et immatriculation', true],
            'vat_number' => [24, 'Numéro de TVA', false],
            'tax_notice' => [300, 'Mention du régime de TVA', false],
            'additional_mentions' => [900, 'Mentions propres à votre activité', false],
            'discount' => [220, 'Conditions d’escompte', true],
            'late_fees' => [220, 'Pénalités de retard pour les professionnels', false],
        ] as $key => [$limit, $label, $required]) {
            $values[$key] = RequestInput::text($post, $key, $limit, $label, $required);
        }

        $regime = $post['regime'] ?? '';
        if (!in_array($regime, ['franchise', 'taxable'], true) || !preg_match('/^\d{9}$/D', $values['siren'])
            || !preg_match('/^\d{5}$/D', $values['postal'])
        ) {
            throw new DomainException('Vérifiez le SIREN, le code postal et le régime de TVA.');
        }
        if ($regime === 'franchise' && $values['tax_notice'] === '') {
            throw new DomainException('Indiquez la mention fiscale officielle applicable à votre franchise en base.');
        }
        if ($regime === 'taxable' && !preg_match('/^FR[A-Z0-9]{2}\d{9}$/D', strtoupper($values['vat_number']))) {
            throw new DomainException('Indiquez le numéro de TVA français de l’entreprise.');
        }
        $values['regime'] = $regime;

        return $values;
    }

    public static function invoice(array $post, array $issuer, string $issueOn): array
    {
        $fields = [];
        foreach ([
            'buyer_name' => [180, 'Nom de facturation', true],
            'buyer_address' => [200, 'Adresse de facturation', true],
            'buyer_postal' => [5, 'Code postal de facturation', true],
            'buyer_city' => [120, 'Ville de facturation', true],
            'buyer_siren' => [9, 'SIREN du client', false],
            'buyer_vat' => [24, 'Numéro de TVA du client', false],
            'buyer_reference' => [100, 'Bon de commande du client', false],
        ] as $key => [$limit, $label, $required]) {
            $fields[$key] = RequestInput::text($post, $key, $limit, $label, $required);
        }

        $buyer = $post['buyer_type'] ?? '';
        if (!in_array($buyer, ['individual','business'], true) || !preg_match('/^\d{5}$/D', $fields['buyer_postal'])) {
            throw new DomainException('Vérifiez le type de client et son code postal français.');
        }
        if ($buyer === 'business' && (!preg_match('/^\d{9}$/D', $fields['buyer_siren']) || $issuer['late_fees'] === '')) {
            throw new DomainException('Pour un client professionnel, indiquez son SIREN et configurez les pénalités de retard.');
        }
        if ($fields['buyer_vat'] !== '' && !preg_match('/^FR[A-Z0-9]{2}\d{9}$/D', strtoupper($fields['buyer_vat']))) {
            throw new DomainException('Le numéro de TVA renseigné doit être un numéro français valide en apparence.');
        }
        $serviceOn = self::date($post, 'service_on', 'Date de la prestation');
        $dueOn = self::date($post, 'due_on', 'Date limite de règlement');
        if ($serviceOn > $issueOn || $dueOn < $issueOn) {
            throw new DomainException('Vérifiez la date de réalisation et la date limite de règlement.');
        }
        $rate = $issuer['regime'] === 'taxable'
            ? Money::cents(RequestInput::text($post, 'vat_rate', 6, 'Taux de TVA', true), 3) : 0;
        if ($issuer['regime'] === 'taxable' && $rate === 0 && $issuer['tax_notice'] === '') {
            throw new DomainException('Indiquez la mention fiscale applicable pour un taux à zéro.');
        }
        if ($rate > 10000) {
            throw new DomainException('Le taux de TVA doit être compris entre 0 et 100 %.');
        }
        $fields['buyer_type'] = $buyer;
        $fields['service_on'] = $serviceOn;
        $fields['due_on'] = $dueOn;
        $fields['vat_rate'] = $rate;

        return $fields;
    }
}
