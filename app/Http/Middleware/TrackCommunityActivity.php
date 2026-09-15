<?php

namespace App\Http\Middleware;

use App\Models\CommunityUser;
use App\Services\Community\CommunitySessionTracker;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackCommunityActivity
{
    public function __construct(private readonly CommunitySessionTracker $tracker) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = auth('community')->user();
        if ($user instanceof CommunityUser) {
            $this->tracker->touch($request, $user);
        }

        return $next($request);
    }
}
