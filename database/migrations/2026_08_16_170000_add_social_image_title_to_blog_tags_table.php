<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('blog_tags', function (Blueprint $table): void {
            $table->string('social_image_title')->nullable()->after('og_description');
        });
    }

    public function down(): void
    {
        Schema::table('blog_tags', function (Blueprint $table): void {
            $table->dropColumn('social_image_title');
        });
    }
};
