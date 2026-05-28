<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');

        // Livewire upload uses a signed URL; CSRF on the same request often fails in admin (session / multipart).
        // Patched Livewire endpoint: /lw-{hash}/upload (see script_ai/patch-livewire-upload.sh).
        $middleware->validateCsrfTokens(except: [
            'lw-*/upload',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
