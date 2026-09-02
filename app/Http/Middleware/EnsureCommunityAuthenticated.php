<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCommunityAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth('community')->check()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Требуется вход в сообщество.'], 401);
            }

            if ($request->isMethod('GET')) {
                $request->session()->put('url.intended', $request->fullUrl());
            }

            return redirect()->route('community.login');
        }

        return $next($request);
    }
}
