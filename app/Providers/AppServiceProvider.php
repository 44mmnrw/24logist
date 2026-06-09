<?php

namespace App\Providers;

use App\Services\LandingPageService;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Livewire\Features\SupportFileUploads\FileUploadController;

class AppServiceProvider extends ServiceProvider
{
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

        Filament::serving(function (): void {
            app()->setLocale('ru');
        });

        View::composer(['components.landing.*', 'errors.*'], function ($view): void {
            $view->with('landing', app(LandingPageService::class));
        });
    }
}
