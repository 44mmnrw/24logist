<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCommunityOnboarded
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth('community')->user();

        if ($user !== null && ! $user->isOnboarded()) {
            return redirect()->route('community.onboarding');
        }

        return $next($request);
    }
}
