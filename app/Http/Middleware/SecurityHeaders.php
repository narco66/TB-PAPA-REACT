<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        $isProd = app()->environment('production');

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('X-Permitted-Cross-Domain-Policies', 'none');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=(), usb=(), interest-cohort=()');
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin');
        $response->headers->set('Cross-Origin-Resource-Policy', 'same-origin');

        if ($isProd) {
            $response->headers->set('Strict-Transport-Security', 'max-age=63072000; includeSubDomains; preload');
        }

        if (! $response->headers->has('Content-Security-Policy')) {
            $csp = $this->buildCsp($isProd);
            $response->headers->set('Content-Security-Policy', $csp);
        }

        $response->headers->remove('X-Powered-By');
        $response->headers->remove('Server');

        return $response;
    }

    protected function buildCsp(bool $isProd): string
    {
        $scriptSrc = $isProd
            ? "'self' 'unsafe-inline'"
            : "'self' 'unsafe-inline' 'unsafe-eval' http://localhost:* http://127.0.0.1:*";

        $styleSrc = $isProd
            ? "'self' 'unsafe-inline' https://fonts.bunny.net"
            : "'self' 'unsafe-inline' https://fonts.bunny.net http://localhost:* http://127.0.0.1:*";

        $fontSrc = $isProd
            ? "'self' data: https://fonts.bunny.net"
            : "'self' data: https://fonts.bunny.net http://localhost:* http://127.0.0.1:*";
        $imgSrc = "'self' data: blob:";
        $connectSrc = $isProd ? "'self'" : "'self' ws://localhost:* ws://127.0.0.1:* http://localhost:* http://127.0.0.1:*";
        $frameSrc = "'self'";
        $objectSrc = "'none'";

        return implode('; ', [
            "default-src 'self'",
            "script-src {$scriptSrc}",
            "style-src {$styleSrc}",
            "font-src {$fontSrc}",
            "img-src {$imgSrc}",
            "connect-src {$connectSrc}",
            "frame-src {$frameSrc}",
            "frame-ancestors 'self'",
            "form-action 'self'",
            "base-uri 'self'",
            "object-src {$objectSrc}",
            ...($isProd ? ['upgrade-insecure-requests'] : []),
        ]);
    }
}
