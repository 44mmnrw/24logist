<?php

use App\Console\Commands\CheckSeoPositions;
use App\Console\Commands\GenerateBlogCardImages;
use App\Console\Commands\GenerateBlogTagImages;
use App\Console\Commands\ImportWordstatCsv;
use App\Http\Middleware\EnforceCanonicalUrl;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withCommands([
        ImportWordstatCsv::class,
        CheckSeoPositions::class,
        GenerateBlogCardImages::class,
        GenerateBlogTagImages::class,
    ])
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');
        $middleware->web(prepend: [
            EnforceCanonicalUrl::class,
        ]);

        // Livewire upload uses a signed URL; CSRF on the same request often fails in admin (session / multipart).
        // Patched Livewire endpoint: /lw-{hash}/upload (see script_ai/patch-livewire-upload.sh).
        $middleware->validateCsrfTokens(except: [
            'lw-*/upload',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
