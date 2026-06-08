<?php

namespace App\Http\Controllers;

use App\Support\AppleTouchIcon;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AppleTouchIconController extends Controller
{
    public function __invoke(): BinaryFileResponse|Response
    {
        $path = AppleTouchIcon::ensureCached();

        if ($path === null || ! is_file($path)) {
            abort(404);
        }

        return response()->file($path, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
