<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seo_keywords', function (Blueprint $table): void {
            $table->string('source_type', 64)->nullable()->after('device')->index();
        });
    }

    public function down(): void
    {
        Schema::table('seo_keywords', function (Blueprint $table): void {
            $table->dropColumn('source_type');
        });
    }
};
