<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('landing_sections', function (Blueprint $table) {
            $table->string('seo_h1')->nullable()->after('title');
        });

        DB::table('landing_sections')
            ->where('slug', 'hero')
            ->whereNull('seo_h1')
            ->update([
                'seo_h1' => 'CRM для экспедиторов: заявки, ЭТрН и контроль рейсов',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        Schema::table('landing_sections', function (Blueprint $table) {
            $table->dropColumn('seo_h1');
        });
    }
};
