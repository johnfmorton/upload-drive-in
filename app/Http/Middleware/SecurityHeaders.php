<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        // Generate a per-request nonce and wire it into Vite before Blade renders.
        // Vite::useCspNonce() creates a random nonce and stamps it on all <script>
        // and <link> tags that @vite emits. We store it on the request so Blade
        // templates can access it via the @cspNonce directive.
        $nonce = Vite::useCspNonce();
        $request->attributes->set('csp-nonce', $nonce);

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
            $headerName = config('app.csp_enforce', false)
                ? 'Content-Security-Policy'
                : 'Content-Security-Policy-Report-Only';

            $response->headers->set($headerName, $this->buildCsp($nonce));
        }

        return $response;
    }

    private function buildCsp(string $nonce): string
    {
        $viteOrigin = '';
        if (app()->environment('local') && ($viteUrl = config('app.asset_url') ?: env('VITE_DEV_SERVER_URL'))) {
            $viteOrigin = ' ' . rtrim($viteUrl, '/');
        }

        // 'unsafe-eval' is required by Alpine.js's standard build (expression engine).
        // Migrating to @alpinejs/csp would allow removing it but requires rewriting 54+ components.
        $directives = [
            "default-src 'self'",
            "script-src 'self' 'nonce-{$nonce}' 'unsafe-eval' https://cdn.jsdelivr.net https://cdn.tailwindcss.com" . $viteOrigin,
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
