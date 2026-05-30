<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('yandex_metrika_enabled')->default(false);
            $table->string('yandex_metrika_counter_id', 20)->nullable();
            $table->boolean('yandex_metrika_webvisor')->default(true);
            $table->boolean('yandex_metrika_clickmap')->default(true);
            $table->boolean('yandex_metrika_track_links')->default(true);
            $table->boolean('yandex_metrika_accurate_track_bounce')->default(true);
            $table->timestamps();
        });

        $now = now();

        DB::table('site_settings')->insert([
            'id' => 1,
            'yandex_metrika_enabled' => false,
            'yandex_metrika_counter_id' => null,
            'yandex_metrika_webvisor' => true,
            'yandex_metrika_clickmap' => true,
            'yandex_metrika_track_links' => true,
            'yandex_metrika_accurate_track_bounce' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('site_settings');
    }
};
