<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->string('og_title')->nullable()->after('favicon_path');
            $table->text('og_description')->nullable()->after('og_title');
            $table->string('og_image_path')->nullable()->after('og_description');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn(['og_title', 'og_description', 'og_image_path']);
        });
    }
};
