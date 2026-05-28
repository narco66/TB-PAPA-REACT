<?php

namespace App\Auth\TwoFactor;

trait TwoFactorAuthenticatable
{
    public function hasEnabledTwoFactorAuthentication(): bool
    {
        return ! is_null($this->two_factor_secret) && ! is_null($this->two_factor_confirmed_at);
    }

    public function hasPendingTwoFactorAuthentication(): bool
    {
        return ! is_null($this->two_factor_secret) && is_null($this->two_factor_confirmed_at);
    }

    public function recoveryCodes(): array
    {
        if (! $this->two_factor_recovery_codes) {
            return [];
        }

        return json_decode(decrypt($this->two_factor_recovery_codes), true) ?? [];
    }

    public function replaceRecoveryCode(string $code): void
    {
        $codes = collect($this->recoveryCodes())
            ->reject(fn ($c) => hash_equals($c, $code))
            ->values()
            ->all();

        $this->forceFill([
            'two_factor_recovery_codes' => encrypt(json_encode($codes)),
        ])->save();
    }
}
