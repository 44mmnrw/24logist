<?php

use App\Services\LandingPageService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('landing_sections')->where('slug', 'pricing_wide')->exists()) {
            return;
        }

        $order = (int) (DB::table('landing_sections')->where('slug', 'pricing')->value('sort_order') ?? 7) + 1;
        DB::table('landing_sections')->where('sort_order', '>=', $order)->increment('sort_order');

        $now = now();
        DB::table('landing_sections')->insert([
            'slug' => 'pricing_wide',
            'name' => 'Широкий тариф',
            'anchor' => 'pricing-wide',
            'title' => 'Индивидуальный тариф для вашей компании',
            'is_active' => true,
            'sort_order' => $order,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $planId = DB::table('landing_blocks')->insertGetId([
            'section_slug' => 'pricing_wide',
            'block_type' => 'plan',
            'title' => 'Корпорация',
            'subtitle' => 'Условия под задачи вашей команды',
            'price' => 'По запросу',
            'description' => 'Количество рабочих мест по договору',
            'tag' => 'Хит',
            'button_text' => 'Запросить расчёт',
            'link' => '/pages/contacts',
            'button_style' => 'primary',
            'is_active' => true,
            'is_highlighted' => true,
            'sort_order' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        foreach (['Индивидуальные лимиты', 'SSO и безопасность', 'SLA и онбординг', 'Выделенный менеджер'] as $index => $title) {
            DB::table('landing_blocks')->insert([
                'section_slug' => 'pricing_wide',
                'block_type' => 'feature',
                'parent_id' => $planId,
                'title' => $title,
                'icon' => 'icon:check',
                'is_active' => true,
                'sort_order' => $index + 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        app(LandingPageService::class)->clearCache();
    }

    public function down(): void
    {
        $order = DB::table('landing_sections')->where('slug', 'pricing_wide')->value('sort_order');
        if ($order === null) {
            return;
        }

        DB::table('landing_blocks')->where('section_slug', 'pricing_wide')->delete();
        DB::table('landing_sections')->where('slug', 'pricing_wide')->delete();
        DB::table('landing_sections')->where('sort_order', '>', $order)->decrement('sort_order');

        app(LandingPageService::class)->clearCache();
    }
};
