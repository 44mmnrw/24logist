<?php

namespace App\Providers\Filament;

use App\Filament\Auth\Login;
use App\Filament\Clusters\Landing\Resources\CmsPages\CmsPageResource;
use App\Filament\Clusters\Landing\Resources\LandingBlocks\LandingBlockResource;
use App\Filament\Clusters\Landing\Resources\LandingLeads\LandingLeadResource;
use App\Filament\Clusters\Landing\Resources\LandingSections\LandingSectionResource;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Assets\Css;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Width;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->assets([
                Css::make('filament-admin', resource_path('css/filament-admin.css')),
            ])
            ->id('admin')
            ->path('admin')
            ->login(Login::class)
            ->brandName('ЛогистРу')
            ->brandLogo(asset('images/logo.svg'))
            ->brandLogoHeight('2rem')
            ->colors([
                'primary' => Color::hex('#1d4ed8'),
            ])
            ->maxContentWidth(Width::Full)
            ->homeUrl(fn (): string => LandingSectionResource::getUrl())
            ->resources([
                LandingSectionResource::class,
                CmsPageResource::class,
                LandingLeadResource::class,
                LandingBlockResource::class,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
