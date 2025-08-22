<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class DebugSetupRequests
{
    public function handle(Request $request, Closure $next): Response
    {
        // Log all setup requests
        Log::info('SETUP REQUEST DEBUG', [
            'method' => $request->method(),
            'url' => $request->url(),
            'path' => $request->path(),
            'route_name' => $request->route()?->getName(),
            'has_csrf_token' => $request->has('_token'),
            'csrf_token_length' => $request->has('_token') ? strlen($request->input('_token')) : 0,
            'session_token_length' => strlen(session()->token()),
            'tokens_match' => $request->has('_token') && $request->input('_token') === session()->token(),
            'headers' => [
                'content-type' => $request->header('Content-Type'),
                'x-csrf-token' => $request->header('X-CSRF-TOKEN'),
                'user-agent' => $request->header('User-Agent'),
            ],
            'input_keys' => array_keys($request->all()),
        ]);

        $response = $next($request);

        // Log response
        Log::info('SETUP RESPONSE DEBUG', [
            'status' => $response->getStatusCode(),
            'route_name' => $request->route()?->getName(),
        ]);

        return $response;
    }
}