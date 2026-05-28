<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Auth\TwoFactorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class TwoFactorChallengeController extends Controller
{
    public function __construct(protected TwoFactorService $twoFactor) {}

    public function show(Request $request): Response|RedirectResponse
    {
        if (! $this->pendingUserId($request)) {
            return redirect()->route('login');
        }

        return Inertia::render('auth/two-factor-challenge');
    }

    public function store(Request $request): RedirectResponse
    {
        $userId = $this->pendingUserId($request);
        if (! $userId) {
            return redirect()->route('login');
        }

        $user = User::find($userId);
        if (! $user) {
            $request->session()->forget(['login.id', 'login.remember']);

            return redirect()->route('login');
        }

        $validated = $request->validate([
            'code' => ['nullable', 'string'],
            'recovery_code' => ['nullable', 'string'],
        ]);

        if (! ($validated['code'] ?? null) && ! ($validated['recovery_code'] ?? null)) {
            throw ValidationException::withMessages([
                'code' => 'Fournissez un code TOTP ou un code de récupération.',
            ]);
        }

        $authenticated = false;
        if ($validated['code'] ?? null) {
            $authenticated = $this->twoFactor->verify($user, $validated['code']);
        }

        if (! $authenticated && ($validated['recovery_code'] ?? null)) {
            $authenticated = $this->twoFactor->verifyRecoveryCode($user, $validated['recovery_code']);
        }

        if (! $authenticated) {
            throw ValidationException::withMessages([
                'code' => 'Code invalide.',
            ]);
        }

        Auth::login($user, $request->session()->get('login.remember', false));
        $request->session()->forget(['login.id', 'login.remember']);
        $request->session()->regenerate();

        $user->forceFill([
            'derniere_connexion_at' => now(),
            'failed_login_attempts' => 0,
        ])->save();

        return redirect()->intended(route('dashboard'));
    }

    protected function pendingUserId(Request $request): ?int
    {
        return $request->session()->get('login.id');
    }
}
