<?php

namespace App\Support;

use Illuminate\Support\Carbon;

class Pesel
{
    private const WEIGHTS = [1, 3, 7, 9, 1, 3, 7, 9, 1, 3];

    public static function isValid(string $pesel): bool
    {
        return preg_match('/^\d{11}$/', $pesel) === 1
            && self::control($pesel) === (int) $pesel[10]
            && self::birthDate($pesel) !== null;
    }

    public static function birthDate(string $pesel): ?Carbon
    {
        if (preg_match('/^\d{11}$/', $pesel) !== 1) {
            return null;
        }

        $month = (int) substr($pesel, 2, 2);
        $year = self::century($month) + (int) substr($pesel, 0, 2);
        $month %= 20;
        $day = (int) substr($pesel, 4, 2);

        if ($month < 1 || $month > 12 || ! checkdate($month, $day, $year)) {
            return null;
        }

        $date = Carbon::create($year, $month, $day)->startOfDay();

        return $date->isFuture() ? null : $date;
    }

    public static function gender(string $pesel): ?string
    {
        if (preg_match('/^\d{11}$/', $pesel) !== 1) {
            return null;
        }

        return (int) $pesel[9] % 2 === 1 ? 'M' : 'K';
    }

    public static function generate(string $gender, ?Carbon $birthDate = null): string
    {
        $birthDate ??= Carbon::now()->subYears(random_int(18, 70))->subDays(random_int(0, 364));

        $month = $birthDate->month + match (intdiv($birthDate->year, 100)) {
            18 => 80,
            20 => 20,
            21 => 40,
            22 => 60,
            default => 0,
        };

        $genderDigit = $gender === 'M' ? [1, 3, 5, 7, 9][random_int(0, 4)] : [0, 2, 4, 6, 8][random_int(0, 4)];

        $pesel = sprintf('%02d%02d%02d%03d%d', $birthDate->year % 100, $month, $birthDate->day, random_int(0, 999), $genderDigit);

        return $pesel.self::control($pesel);
    }

    private static function century(int $month): int
    {
        return match (intdiv($month, 20)) {
            0 => 1900,
            1 => 2000,
            2 => 2100,
            3 => 2200,
            default => 1800,
        };
    }

    private static function control(string $pesel): int
    {
        $sum = 0;
        foreach (self::WEIGHTS as $i => $weight) {
            $sum += (int) $pesel[$i] * $weight;
        }

        return (10 - $sum % 10) % 10;
    }
}
