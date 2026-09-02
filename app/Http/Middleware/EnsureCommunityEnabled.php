<?php

namespace App\Http\Middleware;

use App\Services\SiteSettingsService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCommunityEnabled
{
    public function __construct(private readonly SiteSettingsService $settings) {}

    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($this->settings->communityEnabled(), 404);

        return $next($request);
    }
}
