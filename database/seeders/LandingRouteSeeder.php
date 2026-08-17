<?php

namespace Database\Seeders;

use App\Models\LandingSection;
use App\Services\LandingPageService;
use Illuminate\Database\Seeder;

class LandingRouteSeeder extends Seeder
{
    /** @var array<string, string> */
    private const DEFAULT_STOPS = [
        'hero' => 'Старт',
        'why' => 'Функционал',
        'platform' => 'Платформа',
        'pricing' => 'Тарифы',
        'epd_platform' => 'ЭПД',
        'mobile' => 'Мобильный кабинет',
        'driver_cabinet' => 'ЛК водителя',
        'quiz' => 'Подбор тарифа',
        'final_cta' => 'Финиш',
    ];

    public function run(): void
    {
        foreach (self::DEFAULT_STOPS as $slug => $label) {
            LandingSection::query()
                ->where('slug', $slug)
                ->whereNull('route_enabled')
                ->update([
                    'route_enabled' => true,
                    'route_label' => $label,
                ]);
        }

        LandingSection::query()
            ->whereNull('route_enabled')
            ->update(['route_enabled' => false]);

        app(LandingPageService::class)->clearCache();
    }
}
