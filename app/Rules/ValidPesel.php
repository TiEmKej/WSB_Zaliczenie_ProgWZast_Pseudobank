<?php

namespace App\Rules;

use App\Support\Pesel;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidPesel implements ValidationRule
{
    public function __construct(private ?string $gender = null)
    {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $pesel = (string) $value;

        if (! Pesel::isValid($pesel)) {
            $fail('Numer PESEL ma nieprawidłową sumę kontrolną lub datę urodzenia.');

            return;
        }

        if ($this->gender !== null && Pesel::gender($pesel) !== $this->gender) {
            $fail('Płeć zapisana w numerze PESEL nie zgadza się z wybraną płcią.');
        }
    }
}
