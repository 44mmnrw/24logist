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
        $defaults = [
            'additional_title' => 'Дополнительные возможности',
            'users_label' => 'Количество пользователей',
            'users_decrease_label' => 'Уменьшить количество пользователей',
            'users_increase_label' => 'Увеличить количество пользователей',
            'users_min' => 1,
            'users_max' => 20,
            'users_default' => 1,
            'currency_suffix' => '₽/мес',
            'year_currency_suffix' => '₽/год',
            'workplace_one' => 'рабочее место',
            'workplace_few' => 'рабочих места',
            'workplace_many' => 'рабочих мест',
            'period_label' => 'Период оплаты',
            'month_label' => 'Месяц',
            'year_label' => 'Год',
        ];

        $plan->update([
            'description' => blank($plan->description) || $plan->description === 'за одного пользователя в месяц'
                ? 'за {users} {workplaces}'
                : $plan->description,
            'extra' => array_merge($defaults, $extra),
        ]);

        app(LandingPageService::class)->clearCache();
    }

    public function down(): void
    {
        // Keep any note edited in the admin panel.
    }
};
