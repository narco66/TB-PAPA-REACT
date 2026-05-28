<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class StrongPassword implements ValidationRule
{
    public function __construct(protected int $min = 12) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('Le mot de passe doit être une chaîne de caractères.');

            return;
        }

        if (mb_strlen($value) < $this->min) {
            $fail("Le mot de passe doit comporter au moins {$this->min} caractères.");

            return;
        }

        if (! preg_match('/[a-z]/', $value)) {
            $fail('Le mot de passe doit contenir au moins une minuscule.');

            return;
        }

        if (! preg_match('/[A-Z]/', $value)) {
            $fail('Le mot de passe doit contenir au moins une majuscule.');

            return;
        }

        if (! preg_match('/[0-9]/', $value)) {
            $fail('Le mot de passe doit contenir au moins un chiffre.');

            return;
        }

        if (! preg_match('/[^A-Za-z0-9]/', $value)) {
            $fail('Le mot de passe doit contenir au moins un caractère spécial.');
        }
    }
}
