<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Security headers applied to all responses
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        if (app()->environment('production')) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        // CSP only on HTML responses
        if (str_contains($response->headers->get('Content-Type', ''), 'text/html')) {
            $response->headers->set('Content-Security-Policy', $this->buildCsp());
        }

        return $response;
    }

    private function buildCsp(): string
    {
        $viteOrigin = '';
        if (app()->environment('local') && ($viteUrl = config('app.asset_url') ?: env('VITE_DEV_SERVER_URL'))) {
            $viteOrigin = ' ' . rtrim($viteUrl, '/');
        }

        // unsafe-inline and unsafe-eval are required by Shoelace web components and Alpine.js.
        // TODO: Migrate to nonce-based CSP to eliminate these directives.
        $directives = [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval'" . $viteOrigin,
            "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.bunny.net" . $viteOrigin,
            "img-src 'self' data: blob:",
            "font-src 'self' https://cdn.jsdelivr.net https://fonts.bunny.net",
            "connect-src 'self'" . ($viteOrigin ? " {$viteOrigin} wss:" : ''),
            "frame-src 'none'",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
        ];

        return implode('; ', $directives);
    }
}
