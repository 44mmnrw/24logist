<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $exists = DB::table('landing_sections')->where('slug', 'additional_options')->exists();

        if (! $exists) {
            DB::table('landing_sections')->where('sort_order', '>=', 7)->increment('sort_order');
        }

        DB::table('landing_sections')->updateOrInsert(
            ['slug' => 'additional_options'],
            [
                'name' => 'Дополнительные возможности',
                'kicker' => 'Подключаются отдельно',
                'title' => 'Дополнительные возможности',
                'subtitle' => 'Расширяйте систему по мере роста — подключайте только то, что нужно именно вам, и платите только за это.',
                'is_active' => true,
                'sort_order' => 7,
                'updated_at' => $now,
                'created_at' => $now,
            ],
        );

        foreach ([
            ['title' => 'Дополнительное рабочее место', 'description' => 'Каждое место сверх тарифного лимита оплачивается отдельно.', 'price' => '1 200 ₽/мес', 'icon' => 'icon:additional-seat'],
            ['title' => 'Дополнительный пакет ЭПД', 'description' => 'Докупите пакет электронных перевозочных документов сверх включённого объёма.', 'price' => 'по пакетам', 'icon' => 'icon:additional-epd'],
            ['title' => 'Дополнительное место в облаке', 'description' => 'Расширьте хранилище для документов и вложений на любой объём.', 'price' => 'по объёму', 'icon' => 'icon:additional-cloud'],
        ] as $index => $option) {
            DB::table('landing_blocks')->updateOrInsert(
                ['section_slug' => 'additional_options', 'block_type' => 'option', 'sort_order' => $index + 1],
                [...$option, 'is_active' => true, 'is_highlighted' => false, 'updated_at' => $now, 'created_at' => $now],
            );
        }
    }

    public function down(): void
    {
        DB::table('landing_blocks')->where('section_slug', 'additional_options')->delete();
        DB::table('landing_sections')->where('slug', 'additional_options')->delete();
        DB::table('landing_sections')->where('sort_order', '>', 7)->decrement('sort_order');
    }
};
