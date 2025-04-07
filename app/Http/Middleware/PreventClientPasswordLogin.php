<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PreventClientPasswordLogin
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is('login') && $request->method() === 'POST') {
            $user = \App\Models\User::where('email', $request->email)->first();

            if ($user && $user->isClient()) {
                return redirect()->route('home')
                    ->with('error', 'Client users must log in using email verification.');
            }
        }

        return $next($request);
    }
}
