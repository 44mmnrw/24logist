<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table): void {
            $table->boolean('community_enabled')->nullable()->after('epd_popup_registration_enabled');
            $table->boolean('community_max_enabled')->nullable()->after('community_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table): void {
            $table->dropColumn(['community_enabled', 'community_max_enabled']);
        });
    }
};
