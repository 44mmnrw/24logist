<?php

namespace App\Http\Controllers;

use App\Support\WebAppManifest;
use Illuminate\Http\JsonResponse;

class ManifestController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json(WebAppManifest::data(), 200, [
            'Content-Type' => 'application/manifest+json; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
