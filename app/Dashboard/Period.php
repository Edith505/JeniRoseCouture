<?php

declare(strict_types=1);

namespace App\Dashboard;

use DateTimeImmutable;
use DateTimeZone;
use DomainException;

final class Period
{
    public const CHOICES = [
        'today' => 'Aujourd’hui',
        'week' => 'Cette semaine',
        'month' => 'Ce mois',
        'year' => 'Cette année',
        'custom' => 'Période personnalisée',
    ];

    private function __construct(
        public readonly string $choice,
        public readonly DateTimeImmutable $start,
        public readonly DateTimeImmutable $end,
    ) {
    }

    public static function fromQuery(array $query): self
    {
        $choice = $query['period'] ?? 'month';
        if (!is_string($choice) || !isset(self::CHOICES[$choice])) {
            throw new DomainException('Choisissez une période valide.');
        }

        $local = new DateTimeZone('Europe/Paris');
        $today = new DateTimeImmutable('today', $local);
        [$from, $until] = match ($choice) {
            'today' => [$today, $today->modify('+1 day')],
            'week' => [$today->modify('monday this week'), $today->modify('monday this week')->modify('+1 week')],
            'month' => [$today->modify('first day of this month'), $today->modify('first day of next month')],
            'year' => [$today->setDate((int) $today->format('Y'), 1, 1), $today->setDate((int) $today->format('Y') + 1, 1, 1)],
            'custom' => [self::date($query['from'] ?? null, $local), self::date($query['to'] ?? null, $local)->modify('+1 day')],
        };
        if ($from >= $until || $from->diff($until)->days > 366) {
            throw new DomainException('Choisissez des dates dans l’ordre, sur 366 jours au maximum.');
        }

        return new self($choice, $from, $until);
    }

    public static function utc(DateTimeImmutable $date): string
    {
        return $date->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
    }

    public function bounds(): array
    {
        return [self::utc($this->start), self::utc($this->end)];
    }

    public function buckets(): array
    {
        $days = $this->start->diff($this->end)->days;
        $buckets = [];
        for ($from = $this->start; $from < $this->end; $from = $until) {
            $until = match (true) {
                $days <= 31 => $from->modify('+1 day'),
                $days <= 92 => $from->modify('+7 days'),
                default => $from->modify('first day of next month'),
            };
            $until = min($until, $this->end);
            $label = match (true) {
                $days <= 31 => $from->format('d/m'),
                $days <= 92 => $from->format('d/m') . '–' . $until->modify('-1 day')->format('d/m'),
                default => $from->format('m/Y'),
            };
            $buckets[] = [$label, self::utc($from), self::utc($until)];
        }

        return $buckets;
    }

    private static function date(mixed $value, DateTimeZone $zone): DateTimeImmutable
    {
        $date = is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}$/D', $value) === 1
            ? DateTimeImmutable::createFromFormat('!Y-m-d', $value, $zone) : false;
        if (!$date || $date->format('Y-m-d') !== $value || (int) $date->format('Y') < 1980 || (int) $date->format('Y') > 9998) {
            throw new DomainException('Saisissez deux dates valides.');
        }

        return $date;
    }
}
