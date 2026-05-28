<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceTwoFactor
{
    /**
     * Rôles institutionnels devant obligatoirement activer la 2FA.
     */
    public const ROLES_SENSIBLES = [
        'president',
        'vice_president',
        'commissaire',
        'secretaire_general',
        'audit_interne',
        'controle_financier',
        'admin_fonctionnel',
        'admin_technique',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        if (! $user->hasAnyRole(self::ROLES_SENSIBLES)) {
            return $next($request);
        }

        if ($user->hasEnabledTwoFactorAuthentication()) {
            return $next($request);
        }

        $allowedRoutes = [
            'two-factor.setup',
            'two-factor.enable',
            'two-factor.confirm',
            'logout',
        ];

        if (in_array($request->route()?->getName(), $allowedRoutes, true)) {
            return $next($request);
        }

        return redirect()->route('two-factor.setup')
            ->with('warning', 'Votre rôle institutionnel requiert l\'activation de la double authentification.');
    }
}
