<?php

namespace App\Http\Controllers;

use App\Support\PwaIcons;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PwaIconController extends Controller
{
    public function __invoke(int $size): BinaryFileResponse|Response
    {
        $path = PwaIcons::ensureCached($size);

        if ($path === null || ! is_file($path)) {
            abort(404);
        }

        return response()->file($path, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
