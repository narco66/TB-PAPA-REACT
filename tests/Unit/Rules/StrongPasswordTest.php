<?php

namespace Tests\Unit\Rules;

use App\Rules\StrongPassword;
use PHPUnit\Framework\TestCase;

class StrongPasswordTest extends TestCase
{
    public function test_accepte_un_mot_de_passe_conforme(): void
    {
        $errors = $this->runRule('Aa1!azertyuiop');
        $this->assertSame([], $errors);
    }

    public function test_refuse_mot_de_passe_trop_court(): void
    {
        $errors = $this->runRule('Aa1!Aa1!');
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('12 caractères', $errors[0]);
    }

    public function test_refuse_sans_minuscule(): void
    {
        $errors = $this->runRule('AAAAAAAA1234!');
        $this->assertStringContainsString('minuscule', $errors[0]);
    }

    public function test_refuse_sans_majuscule(): void
    {
        $errors = $this->runRule('aaaaaaaa1234!');
        $this->assertStringContainsString('majuscule', $errors[0]);
    }

    public function test_refuse_sans_chiffre(): void
    {
        $errors = $this->runRule('Aaaaaaaaaaa!');
        $this->assertStringContainsString('chiffre', $errors[0]);
    }

    public function test_refuse_sans_special(): void
    {
        $errors = $this->runRule('Aaaaaaaaaa11');
        $this->assertStringContainsString('spécial', $errors[0]);
    }

    public function test_seuil_personnalisable(): void
    {
        $rule = new StrongPassword(20);
        $errors = [];
        $rule->validate('password', 'Aa1!azertyuiop', function ($msg) use (&$errors) {
            $errors[] = $msg;
        });
        $this->assertStringContainsString('20 caractères', $errors[0]);
    }

    protected function runRule(string $value): array
    {
        $rule = new StrongPassword;
        $errors = [];
        $rule->validate('password', $value, function ($msg) use (&$errors) {
            $errors[] = $msg;
        });

        return $errors;
    }
}
