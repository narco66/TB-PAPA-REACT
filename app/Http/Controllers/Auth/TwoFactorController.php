<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\TwoFactorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class TwoFactorController extends Controller
{
    public function __construct(protected TwoFactorService $twoFactor) {}

    public function index(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('settings/two-factor', [
            'enabled' => $user->hasEnabledTwoFactorAuthentication(),
            'pending' => $user->hasPendingTwoFactorAuthentication(),
            'qrSvg' => $user->two_factor_secret ? $this->twoFactor->qrCodeSvg($user) : null,
            'secret' => $user->two_factor_secret ? $this->twoFactor->secretChunks($user) : null,
            'recoveryCodes' => $user->hasEnabledTwoFactorAuthentication() ? $user->recoveryCodes() : [],
        ]);
    }

    public function enable(Request $request): RedirectResponse
    {
        $this->twoFactor->generate($request->user());

        return back()->with('success', 'Secret 2FA généré. Scannez le QR puis confirmez avec un code.');
    }

    public function confirm(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'min:6', 'max:8'],
        ]);

        if (! $this->twoFactor->confirm($request->user(), $validated['code'])) {
            throw ValidationException::withMessages([
                'code' => 'Code invalide. Vérifiez l\'heure de votre appareil et réessayez.',
            ]);
        }

        return back()->with('success', 'Double authentification activée. Conservez vos codes de récupération.');
    }

    public function disable(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $this->twoFactor->disable($request->user());

        return back()->with('success', 'Double authentification désactivée.');
    }

    public function regenerateRecoveryCodes(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $this->twoFactor->regenerateRecoveryCodes($request->user());

        return back()->with('success', 'Nouveaux codes de récupération générés.');
    }
}
