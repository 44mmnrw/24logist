<?php

namespace App\Http\Controllers;

use App\Services\SiteSettingsService;
use App\Support\LandingMedia;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FaviconController extends Controller
{
    public function __invoke(SiteSettingsService $settings): BinaryFileResponse|Response
    {
        $meta = $settings->favicon();
        $path = LandingMedia::normalizePath($settings->get()->favicon_path);

        if ($path !== null && Storage::disk('public')->exists($path)) {
            return response()->file(
                Storage::disk('public')->path($path),
                [
                    'Content-Type' => $meta['type'],
                    'Cache-Control' => 'public, max-age=86400',
                ],
            );
        }

        $fallback = public_path('images/favicon.svg');

        if (! is_file($fallback)) {
            abort(404);
        }

        return response()->file($fallback, [
            'Content-Type' => 'image/svg+xml',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
