<?php

use App\Services\LandingPageService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('landing_sections')->where('slug', 'product_showcase')->exists()) {
            return;
        }

        $order = (int) (DB::table('landing_sections')->where('slug', 'pricing')->value('sort_order') ?? 6);
        DB::table('landing_sections')->where('sort_order', '>=', $order)->increment('sort_order');

        $now = now();
        DB::table('landing_sections')->insert([
            'slug' => 'product_showcase',
            'name' => 'Баннеры возможностей',
            'anchor' => 'product-showcase',
            'extra' => json_encode([
                'banners' => [[
                    'title' => 'список заявок',
                    'description' => "с указанием маршрута, сведениям по заказчику и исполнителю\nуказанием менеджера, статуса документа и быстрыми действиями",
                    'image' => null,
                    'alt' => '',
                ]],
            ], JSON_UNESCAPED_UNICODE),
            'is_active' => true,
            'sort_order' => $order,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        app(LandingPageService::class)->clearCache();
    }

    public function down(): void
    {
        $order = DB::table('landing_sections')->where('slug', 'product_showcase')->value('sort_order');
        if ($order === null) {
            return;
        }

        DB::table('landing_sections')->where('slug', 'product_showcase')->delete();
        DB::table('landing_sections')->where('sort_order', '>', $order)->decrement('sort_order');

        app(LandingPageService::class)->clearCache();
    }
};
