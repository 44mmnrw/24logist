<?php

namespace App\Http\Middleware;

use App\Support\CanonicalUrl;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceCanonicalUrl
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $target = CanonicalUrl::redirectTarget($request->getRequestUri());

        if ($target !== null) {
            return redirect()->away($target, 301);
        }

        return $next($request);
    }
}
