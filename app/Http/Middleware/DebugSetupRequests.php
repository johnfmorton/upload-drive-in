<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DebugSetupRequests
{
    public function handle(Request $request, Closure $next): Response
    {
        if (config('app.debug')) {
            \Illuminate\Support\Facades\Log::info('SETUP REQUEST DEBUG', [
                'method' => $request->method(),
                'path' => $request->path(),
                'route_name' => $request->route()?->getName(),
                'input_keys' => array_keys($request->all()),
            ]);
        }

        return $next($request);
    }
}
