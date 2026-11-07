<?php

namespace App\Support;

class AccountNumber
{
    public static function format(?string $number): string
    {
        $number = (string) $number;

        if (preg_match('/^\d{26}$/', $number) !== 1) {
            return $number;
        }

        return substr($number, 0, 2).' '.implode(' ', str_split(substr($number, 2), 4));
    }

    public static function normalize(mixed $number): string
    {
        return preg_replace('/\s+/', '', (string) $number);
    }
}
