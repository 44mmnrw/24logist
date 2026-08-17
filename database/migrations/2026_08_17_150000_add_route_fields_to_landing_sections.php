<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('landing_sections', function (Blueprint $table): void {
            $table->boolean('route_enabled')->nullable()->after('extra');
            $table->string('route_label')->nullable()->after('route_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('landing_sections', function (Blueprint $table): void {
            $table->dropColumn(['route_enabled', 'route_label']);
        });
    }
};
