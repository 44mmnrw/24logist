<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('landing_sections')->where('slug', 'growth')->exists()) {
            return;
        }

        DB::table('landing_sections')->where('sort_order', '>=', 9)->increment('sort_order');

        $now = now();

        DB::table('landing_sections')->insert([
            'slug' => 'growth',
            'name' => 'Рост и эффективность',
            'title' => 'Повышайте эффективность и растите вместе с нами',
            'extra' => json_encode([
                'lead_prefix' => 'Работа в нашей системе освободит от',
                'lead_highlight' => '30 до 60% времени',
                'lead_suffix' => ', которое вы раньше тратили на составление, редактирование и учёт транспортных документов в таблицах или сторонних сервисах.',
                'paragraph_two' => 'Это время вы сможете направить на поиск новых клиентов и работу с действующими заказчиками.',
                'paragraph_three' => 'Занимайтесь новыми проектами, не отвлекаясь от текущих задач, имея круглосуточный доступ к личному кабинету с любого из ваших устройств.',
                'customer_names' => [
                    ['name' => 'ООО "ГК «ЛОГОС»"'],
                    ['name' => 'АО "УКЗ"'],
                    ['name' => 'ООО "БУГУЛЬМИНСКИЙ СЕЛЬСКОХОЗЯЙСТВЕННЫЙ РЫНОК"'],
                    ['name' => 'ООО "МЕТАЛЛИНВЕСТСПБ"'],
                    ['name' => 'ООО "КЛИМАТ-КОМПЛЕКС"'],
                ],
            ], JSON_UNESCAPED_UNICODE),
            'is_active' => true,
            'sort_order' => 9,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->forgetLandingCache();
    }

    public function down(): void
    {
        if (! DB::table('landing_sections')->where('slug', 'growth')->exists()) {
            return;
        }

        DB::table('landing_sections')->where('slug', 'growth')->delete();
        DB::table('landing_sections')->where('sort_order', '>', 9)->decrement('sort_order');

        $this->forgetLandingCache();
    }

    private function forgetLandingCache(): void
    {
        Cache::forget('landing.page.content.v3');
        Cache::forget('landing.page.content');
    }
};
