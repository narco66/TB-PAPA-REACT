<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Services\Auth\TwoFactorService;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class TwoFactorChallengeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
    }

    public function test_login_avec_2fa_redirige_vers_challenge(): void
    {
        $user = $this->createUserWith2fa();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'Password@2026',
        ]);

        $response->assertRedirect(route('two-factor.challenge'));
        $this->assertGuest();
    }

    public function test_challenge_valide_code_totp_correct_et_authentifie(): void
    {
        $user = $this->createUserWith2fa();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'Password@2026',
        ]);

        $secret = decrypt($user->two_factor_secret);
        $validCode = app(Google2FA::class)->getCurrentOtp($secret);

        $response = $this->post('/two-factor-challenge', ['code' => $validCode]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_challenge_refuse_code_totp_invalide(): void
    {
        $user = $this->createUserWith2fa();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'Password@2026',
        ]);

        $response = $this->from(route('two-factor.challenge'))
            ->post('/two-factor-challenge', ['code' => '000000']);

        $response->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    public function test_challenge_accepte_recovery_code_a_usage_unique(): void
    {
        $user = $this->createUserWith2fa();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'Password@2026',
        ]);

        $recoveryCode = $user->recoveryCodes()[0];

        $response = $this->post('/two-factor-challenge', ['recovery_code' => $recoveryCode]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);

        $user->refresh();
        $this->assertNotContains($recoveryCode, $user->recoveryCodes());
    }

    public function test_challenge_sans_session_pending_redirige_vers_login(): void
    {
        $response = $this->get('/two-factor-challenge');
        $response->assertRedirect('/login');
    }

    protected function createUserWith2fa(): User
    {
        $user = User::factory()->create([
            'password' => Hash::make('Password@2026'),
            'actif' => true,
        ]);

        app(TwoFactorService::class)->generate($user);
        $user->forceFill(['two_factor_confirmed_at' => now()])->save();
        $user->refresh();

        return $user;
    }
}
