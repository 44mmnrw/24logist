<?php

namespace App\Providers;

use App\Models\LandingLead;
use App\Observers\LandingLeadObserver;
use App\Services\LandingPageService;
use App\Support\CanonicalUrl;
use Filament\Facades\Filament;
use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
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
        FilamentAsset::register([
            Js::make('rich-content-plugins/font-size', resource_path('js/filament/rich-content-plugins/font-size.js'))
                ->loadedOnRequest(),
        ]);

        // Production: canonical URLs from APP_URL (24logist.ru без www).
        // Local: match browser host (Laragon / *.test).
        if (! $this->app->runningInConsole()) {
            if (CanonicalUrl::shouldEnforce()) {
                URL::forceRootUrl(CanonicalUrl::root());

                if (CanonicalUrl::scheme() === 'https') {
                    URL::forceScheme('https');
                }
            } elseif (request()->hasHeader('Host')) {
                URL::forceRootUrl(request()->getSchemeAndHttpHost());
            }
        }

        Filament::serving(function (): void {
            app()->setLocale('ru');
        });

        View::composer(['components.landing.*', 'errors.*'], function ($view): void {
            $view->with('landing', app(LandingPageService::class));
        });

        LandingLead::observe(LandingLeadObserver::class);
    }
}
