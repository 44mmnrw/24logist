<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('landing_blocks', function (Blueprint $table): void {
            $table->string('secondary_tag')->nullable()->after('tag');
        });
    }

    public function down(): void
    {
        Schema::table('landing_blocks', function (Blueprint $table): void {
            $table->dropColumn('secondary_tag');
        });
    }
};
