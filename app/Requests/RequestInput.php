<?php

declare(strict_types=1);

namespace App\Requests;

use App\Media\WebpUpload;
use DateTimeImmutable;
use DateTimeZone;
use DomainException;

final class RequestInput
{
    public static function text(array $input, string $key, int $limit, string $label, bool $required = false): string
    {
        $value = $input[$key] ?? '';
        if (!is_string($value) || !mb_check_encoding($value, 'UTF-8') || str_contains($value, "\0")) {
            throw new DomainException("$label : valeur invalide.");
        }
        $value = trim($value);
        if (mb_strlen($value) > $limit || ($required && $value === '')) {
            throw new DomainException("$label : champ manquant ou trop long.");
        }

        return $value;
    }

    public static function id(mixed $value): ?int
    {
        return filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: null;
    }

    public static function contact(array $input): array
    {
        $email = self::text($input, 'email', 254, 'Adresse e-mail', true);
        $phone = self::text($input, 'phone', 40, 'Téléphone');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new DomainException('Saisissez une adresse e-mail valide.');
        }
        if ($phone !== '' && (preg_match('/^\+?[0-9 .()\-]{6,39}$/D', $phone) !== 1
            || preg_match_all('/\d/', $phone) < 6)
        ) {
            throw new DomainException('Saisissez un numéro de téléphone valide.');
        }

        return [
            self::text($input, 'name', 140, 'Nom', true), $email, $phone,
            self::text($input, 'preference', 450, 'Préférences'),
        ];
    }

    public static function localTime(mixed $value): DateTimeImmutable
    {
        $time = is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/D', $value) === 1
            ? DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $value, new DateTimeZone('Europe/Paris')) : false;
        if (!$time || $time->format('Y-m-d\TH:i') !== $value) {
            throw new DomainException('Choisissez une date et une heure valides pour la France métropolitaine.');
        }

        return $time;
    }

    public static function label(string $utc): string
    {
        return (new DateTimeImmutable($utc, new DateTimeZone('UTC')))
            ->setTimezone(new DateTimeZone('Europe/Paris'))->format('d/m/Y à H:i');
    }

    public static function photos(?array $files): array
    {
        if ($files === null) {
            return [];
        }
        if (!is_array($files['error'] ?? null) || count($files['error']) > 3) {
            throw new DomainException('Joignez au maximum trois photographies.');
        }

        $photos = [];
        foreach ($files['error'] as $index => $error) {
            $photo = WebpUpload::fromRequest([
                'error' => $error,
                'tmp_name' => $files['tmp_name'][$index] ?? null,
                'size' => $files['size'][$index] ?? 0,
            ]);
            if ($photo !== null) {
                $photos[] = $photo;
            }
        }

        return $photos;
    }
}
