<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class TwoFactorService
{
    public function __construct(protected Google2FA $google2fa) {}

    /**
     * Génère un secret TOTP et le stocke (chiffré) sur l'utilisateur — non confirmé.
     */
    public function generate(User $user): void
    {
        $secret = $this->google2fa->generateSecretKey(32);

        $user->forceFill([
            'two_factor_secret' => encrypt($secret),
            'two_factor_recovery_codes' => encrypt(json_encode($this->generateRecoveryCodes())),
            'two_factor_confirmed_at' => null,
        ])->save();
    }

    /**
     * Confirme l'activation 2FA après vérification d'un premier code OTP.
     */
    public function confirm(User $user, string $code): bool
    {
        if (! $this->verify($user, $code)) {
            return false;
        }

        $user->forceFill(['two_factor_confirmed_at' => now()])->save();

        return true;
    }

    /**
     * Désactive complètement la 2FA pour un utilisateur.
     */
    public function disable(User $user): void
    {
        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();
    }

    /**
     * Vérifie un code TOTP à 6 chiffres.
     */
    public function verify(User $user, string $code): bool
    {
        if (! $user->two_factor_secret) {
            return false;
        }

        $code = preg_replace('/\s+/', '', $code);

        if ($this->isDemoCode($code)) {
            return true;
        }

        $secret = decrypt($user->two_factor_secret);

        return (bool) $this->google2fa->verifyKey($secret, $code, 1);
    }

    /**
     * Vérifie un recovery code et le supprime (usage unique).
     */
    public function verifyRecoveryCode(User $user, string $code): bool
    {
        $code = strtolower(trim($code));
        $codes = $user->recoveryCodes();

        foreach ($codes as $stored) {
            if (hash_equals(strtolower($stored), $code)) {
                $user->replaceRecoveryCode($stored);

                return true;
            }
        }

        return false;
    }

    /**
     * Régénère 8 codes de récupération à usage unique.
     */
    public function regenerateRecoveryCodes(User $user): array
    {
        $codes = $this->generateRecoveryCodes();
        $user->forceFill(['two_factor_recovery_codes' => encrypt(json_encode($codes))])->save();

        return $codes;
    }

    /**
     * URL otpauth:// pour scan QR (Google Authenticator, Authy, Microsoft Authenticator).
     */
    public function otpauthUrl(User $user): string
    {
        return $this->google2fa->getQRCodeUrl(
            config('app.name', 'TB-PAPA-CEEAC'),
            $user->email,
            decrypt($user->two_factor_secret),
        );
    }

    /**
     * Rendu SVG du QR code prêt à intégrer dans la page Inertia.
     */
    public function qrCodeSvg(User $user): string
    {
        return QrCode::format('svg')->size(220)->margin(1)->generate($this->otpauthUrl($user));
    }

    /**
     * Secret en clair, en groupes de 4, pour saisie manuelle.
     */
    public function secretChunks(User $user): string
    {
        $secret = decrypt($user->two_factor_secret);

        return trim(chunk_split($secret, 4, ' '));
    }

    protected function generateRecoveryCodes(): array
    {
        return collect(range(1, 8))
            ->map(fn () => Str::lower(Str::random(5)) . '-' . Str::lower(Str::random(5)))
            ->all();
    }

    protected function isDemoCode(string $code): bool
    {
        $demoCode = config('auth.demo_two_factor_code');

        return app()->environment() !== 'production'
            && is_string($demoCode)
            && preg_match('/^\d{6}$/', $demoCode)
            && hash_equals($demoCode, $code);
    }
}
