<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table): void {
            $table->boolean('community_vk_enabled')->default(false)->after('community_max_enabled');
            $table->text('community_vk_client_id')->nullable()->after('community_vk_enabled');
            $table->text('community_vk_redirect_uri')->nullable()->after('community_vk_client_id');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table): void {
            $table->dropColumn([
                'community_vk_enabled',
                'community_vk_client_id',
                'community_vk_redirect_uri',
            ]);
        });
    }
};
