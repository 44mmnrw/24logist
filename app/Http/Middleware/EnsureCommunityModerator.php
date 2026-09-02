<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCommunityModerator
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(auth('community')->user()?->isModerator(), 403);

        return $next($request);
    }
}
