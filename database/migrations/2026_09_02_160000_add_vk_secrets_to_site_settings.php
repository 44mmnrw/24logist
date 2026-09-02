<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table): void {
            $table->text('community_vk_client_secret')->nullable()->after('community_vk_client_id');
            $table->text('community_vk_service_token')->nullable()->after('community_vk_client_secret');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table): void {
            $table->dropColumn([
                'community_vk_client_secret',
                'community_vk_service_token',
            ]);
        });
    }
};
