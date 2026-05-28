<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Services\Auth\TwoFactorService;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class TwoFactorServiceTest extends TestCase
{
    use RefreshDatabase;

    protected TwoFactorService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
        $this->service = app(TwoFactorService::class);
    }

    public function test_generate_cree_un_secret_chiffre_et_des_recovery_codes(): void
    {
        $user = User::factory()->create();

        $this->service->generate($user);
        $user->refresh();

        $this->assertNotNull($user->two_factor_secret);
        $this->assertNotNull($user->two_factor_recovery_codes);
        $this->assertNull($user->two_factor_confirmed_at);
        $this->assertCount(8, $user->recoveryCodes());
    }

    public function test_confirm_valide_un_code_correct_et_active_la_2fa(): void
    {
        $user = User::factory()->create();
        $this->service->generate($user);
        $user->refresh();

        $google2fa = app(Google2FA::class);
        $secret = decrypt($user->two_factor_secret);
        $validCode = $google2fa->getCurrentOtp($secret);

        $this->assertTrue($this->service->confirm($user, $validCode));
        $user->refresh();
        $this->assertNotNull($user->two_factor_confirmed_at);
        $this->assertTrue($user->hasEnabledTwoFactorAuthentication());
    }

    public function test_confirm_refuse_un_code_invalide(): void
    {
        $user = User::factory()->create();
        $this->service->generate($user);
        $user->refresh();

        $this->assertFalse($this->service->confirm($user, '000000'));
        $user->refresh();
        $this->assertNull($user->two_factor_confirmed_at);
    }

    public function test_disable_remet_a_zero_tous_les_champs(): void
    {
        $user = User::factory()->create();
        $this->service->generate($user);
        $user->forceFill(['two_factor_confirmed_at' => now()])->save();

        $this->service->disable($user);
        $user->refresh();

        $this->assertNull($user->two_factor_secret);
        $this->assertNull($user->two_factor_recovery_codes);
        $this->assertNull($user->two_factor_confirmed_at);
    }

    public function test_recovery_code_est_a_usage_unique(): void
    {
        $user = User::factory()->create();
        $this->service->generate($user);
        $user->refresh();

        $code = $user->recoveryCodes()[0];

        $this->assertTrue($this->service->verifyRecoveryCode($user, $code));
        $user->refresh();
        $this->assertFalse($this->service->verifyRecoveryCode($user, $code));
        $this->assertCount(7, $user->recoveryCodes());
    }

    public function test_regenerate_recovery_codes_produit_8_nouveaux_codes(): void
    {
        $user = User::factory()->create();
        $this->service->generate($user);
        $user->refresh();

        $anciens = $user->recoveryCodes();
        $nouveaux = $this->service->regenerateRecoveryCodes($user);

        $this->assertCount(8, $nouveaux);
        $this->assertEmpty(array_intersect($anciens, $nouveaux));
    }
}
