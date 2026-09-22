<?php

use App\Console\Commands\CheckSeoPositions;
use App\Console\Commands\GenerateBlogCardImages;
use App\Console\Commands\GenerateBlogTagImages;
use App\Console\Commands\ImportWordstatCsv;
use App\Http\Middleware\EnforceCanonicalUrl;
use App\Http\Middleware\EnsureCommunityAuthenticated;
use App\Http\Middleware\EnsureCommunityEnabled;
use App\Http\Middleware\EnsureCommunityModerator;
use App\Http\Middleware\EnsureCommunityOnboarded;
use App\Http\Middleware\EnsureReferralPlatformAccess;
use App\Http\Middleware\TrackCommunityActivity;
use App\Http\Middleware\UseRussianCommunityLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
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
            'community/webhooks/max',
            'community/ai/collector/*',
        ]);
        $middleware->alias([
            'community.locale' => UseRussianCommunityLocale::class,
            'community.enabled' => EnsureCommunityEnabled::class,
            'community.auth' => EnsureCommunityAuthenticated::class,
            'community.onboarded' => EnsureCommunityOnboarded::class,
            'community.moderator' => EnsureCommunityModerator::class,
            'community.activity' => TrackCommunityActivity::class,
            'referral.platform' => EnsureReferralPlatformAccess::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (ValidationException $exception, Request $request) {
            if (! $request->routeIs('community.auth.max.approve', 'community.auth.max.session')) {
                return null;
            }

            $agent = (string) $request->userAgent();
            preg_match('/(?:iPhone OS|CPU OS) ([0-9_]+)/', $agent, $ios);
            preg_match('/MAX\/([0-9.]+)/', $agent, $max);
            Log::notice('Community MAX authorization rejected', [
                'route' => $request->route()->getName(),
                'reason' => $request->attributes->get('max_auth_failure_reason', 'request_or_account_validation'),
                'fields' => array_values(array_intersect(['max', 'challenge', 'init_data', 'provider'], array_keys($exception->errors()))),
                'ios_version' => $ios[1] ?? null,
                'max_version' => $max[1] ?? null,
                'challenge_type' => get_debug_type($request->input('challenge')),
            ]);

            // Preserve Laravel's original 422 response and every authentication check.
            return null;
        });
    })->create();
