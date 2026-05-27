<?php

namespace App\Providers;

use Filament\Facades\Filament;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->environment('local') && ! $this->app->runningInConsole()) {
            URL::forceRootUrl(request()->getSchemeAndHttpHost());
        }

        Filament::serving(function (): void {
            app()->setLocale('ru');
        });

        \Illuminate\Support\Facades\View::composer('components.landing.*', function ($view): void {
            $view->with('landing', app(\App\Services\LandingPageService::class));
        });
    }
}
