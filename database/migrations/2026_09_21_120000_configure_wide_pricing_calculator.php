<?php

use App\Models\LandingBlock;
use App\Services\LandingPageService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $plan = LandingBlock::query()
            ->where('section_slug', 'pricing_wide')
            ->where('block_type', 'plan')
            ->first();

        if (! $plan) {
            return;
        }

        $extra = is_array($plan->extra) ? $plan->extra : [];
        $numericPrice = (int) preg_replace('/\D+/', '', (string) $plan->price);

        $plan->update([
            'price' => $numericPrice > 0 ? $numericPrice : 1200,
            'description' => $plan->description === 'Количество рабочих мест по договору'
                ? 'за одного пользователя в месяц'
                : $plan->description,
            'extra' => array_merge([
                'additional_title' => 'Дополнительные возможности',
                'users_label' => 'Количество пользователей',
                'users_min' => 1,
                'users_max' => 20,
                'users_default' => 1,
                'currency_suffix' => '₽/мес',
            ], $extra),
        ]);

        if (! $plan->children()->where('block_type', 'paid_option')->exists()) {
            foreach ([
                ['title' => 'Модуль ЭПД', 'price' => 1500],
                ['title' => 'Расширенная аналитика', 'price' => 2500],
                ['title' => 'Интеграция по API', 'price' => 3000],
            ] as $index => $option) {
                LandingBlock::query()->create([
                    'section_slug' => 'pricing_wide',
                    'block_type' => 'paid_option',
                    'parent_id' => $plan->id,
                    'title' => $option['title'],
                    'price' => $option['price'],
                    'is_active' => true,
                    'sort_order' => $index + 1,
                ]);
            }
        }

        app(LandingPageService::class)->clearCache();
    }

    public function down(): void
    {
        $plan = LandingBlock::query()
            ->where('section_slug', 'pricing_wide')
            ->where('block_type', 'plan')
            ->first();

        if (! $plan) {
            return;
        }

        $plan->children()->where('block_type', 'paid_option')->delete();

        $extra = is_array($plan->extra) ? $plan->extra : [];
        foreach (['additional_title', 'users_label', 'users_min', 'users_max', 'users_default', 'currency_suffix'] as $key) {
            unset($extra[$key]);
        }
        $plan->update(['extra' => $extra]);

        app(LandingPageService::class)->clearCache();
    }
};
