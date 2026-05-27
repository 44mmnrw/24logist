<?php

namespace App\Providers;

use App\Services\LandingPageService;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Livewire\Features\SupportFileUploads\FileUploadController;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            FileUploadController::class,
            \App\Http\Livewire\FileUploadController::class,
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Same as Laragon locally: signed Livewire upload URLs must match the browser host.
        if (! $this->app->runningInConsole() && request()->hasHeader('Host')) {
            URL::forceRootUrl(request()->getSchemeAndHttpHost());
        }

        if (request()->is('livewire-*/update')) {
            $calls = collect(request()->input('components', []))
                ->flatMap(fn ($component) => collect($component['calls'] ?? [])->pluck('method'))
                ->filter()
                ->values()
                ->all();

            Log::error('livewire.update.calls', [
                'host' => request()->getHost(),
                'calls' => $calls,
            ]);
        }

        if (request()->is('livewire-*/upload')) {
            Log::error('livewire.upload.route.hit', [
                'host' => request()->getHost(),
                'files' => count(request()->allFiles()['files'] ?? []),
            ]);
        }

        Filament::serving(function (): void {
            app()->setLocale('ru');
        });

        Livewire::listen('call', function ($component, $method, $params): void {
            if (! in_array($method, ['_startUpload', '_finishUpload', '_uploadErrored'], true)) {
                return;
            }

            Log::error("livewire.{$method}", [
                'component' => $component::class,
                'host' => request()->getHost(),
                'file' => $params[0] ?? null,
            ]);
        });

        View::composer('components.landing.*', function ($view): void {
            $view->with('landing', app(LandingPageService::class));
        });
    }
}
