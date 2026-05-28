<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Services\Auth\TwoFactorService;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class TwoFactorEnableTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
    }

    public function test_utilisateur_peut_demarrer_lenrollment_2fa(): void
    {
        $user = User::factory()->create(['actif' => true]);

        $this->actingAs($user)
            ->post('/settings/two-factor/enable')
            ->assertRedirect();

        $user->refresh();
        $this->assertNotNull($user->two_factor_secret);
        $this->assertTrue($user->hasPendingTwoFactorAuthentication());
    }

    public function test_confirm_active_la_2fa_avec_code_valide(): void
    {
        $user = User::factory()->create(['actif' => true]);
        app(TwoFactorService::class)->generate($user);
        $user->refresh();

        $secret = decrypt($user->two_factor_secret);
        $code = app(Google2FA::class)->getCurrentOtp($secret);

        $this->actingAs($user)
            ->post('/settings/two-factor/confirm', ['code' => $code])
            ->assertRedirect();

        $user->refresh();
        $this->assertTrue($user->hasEnabledTwoFactorAuthentication());
    }

    public function test_disable_exige_password_actuel(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('Password@2026'),
            'actif' => true,
        ]);
        app(TwoFactorService::class)->generate($user);
        $user->forceFill(['two_factor_confirmed_at' => now()])->save();

        $this->actingAs($user)
            ->from('/settings/two-factor')
            ->delete('/settings/two-factor', ['password' => 'wrong'])
            ->assertSessionHasErrors('password');

        $user->refresh();
        $this->assertNotNull($user->two_factor_secret);
    }

    public function test_disable_avec_password_correct_supprime_la_2fa(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('Password@2026'),
            'actif' => true,
        ]);
        app(TwoFactorService::class)->generate($user);
        $user->forceFill(['two_factor_confirmed_at' => now()])->save();

        $this->actingAs($user)
            ->delete('/settings/two-factor', ['password' => 'Password@2026']);

        $user->refresh();
        $this->assertNull($user->two_factor_secret);
        $this->assertFalse($user->hasEnabledTwoFactorAuthentication());
    }
}
