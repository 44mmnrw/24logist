<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table): void {
            $table->boolean('route_calculator_enabled')->default(false)->after('community_max_enabled');
            $table->text('route_api_base_url')->nullable()->after('route_calculator_enabled');
            $table->text('route_api_secret')->nullable()->after('route_api_base_url');
            $table->unsignedTinyInteger('route_api_timeout')->default(15)->after('route_api_secret');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table): void {
            $table->dropColumn([
                'route_calculator_enabled',
                'route_api_base_url',
                'route_api_secret',
                'route_api_timeout',
            ]);
        });
    }
};
