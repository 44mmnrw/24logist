<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table): void {
            $table->boolean('google_analytics_enabled')->default(false)->after('yandex_metrika_accurate_track_bounce');
            $table->string('google_analytics_measurement_id', 32)->nullable()->after('google_analytics_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table): void {
            $table->dropColumn(['google_analytics_enabled', 'google_analytics_measurement_id']);
        });
    }
};
