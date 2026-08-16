<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seo_keyword_clusters', function (Blueprint $table): void {
            $table->string('seed_phrase', 500)->nullable()->after('slug');
        });
    }

    public function down(): void
    {
        Schema::table('seo_keyword_clusters', function (Blueprint $table): void {
            $table->dropColumn('seed_phrase');
        });
    }
};
