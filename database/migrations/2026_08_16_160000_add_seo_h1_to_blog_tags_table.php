<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('blog_tags', function (Blueprint $table): void {
            $table->string('seo_h1')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('blog_tags', function (Blueprint $table): void {
            $table->dropColumn('seo_h1');
        });
    }
};
