<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ContentSecurityPolicy
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only add CSP to HTML responses
        if (!str_contains($response->headers->get('Content-Type', ''), 'text/html')) {
            return $response;
        }

        $viteOrigin = '';
        if (app()->environment('local') && ($viteUrl = config('app.asset_url') ?: env('VITE_DEV_SERVER_URL'))) {
            $viteOrigin = ' ' . rtrim($viteUrl, '/');
        }

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

        $response->headers->set(
            'Content-Security-Policy',
            implode('; ', $directives)
        );

        return $response;
    }
}
