<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;

final class UseRussianCommunityLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        app()->setLocale('ru');
        Carbon::setLocale('ru');

        return $next($request);
    }
}
