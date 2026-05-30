<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var array<string, string> */
    private array $defaults = [
        'hero' => 'hero',
        'features' => 'features',
        'why' => 'why',
        'pricing' => 'pricing',
        'quiz' => 'quiz',
        'faq' => 'faq',
        'final_cta' => 'final-cta',
    ];

    public function up(): void
    {
        Schema::table('landing_sections', function (Blueprint $table) {
            $table->string('anchor', 64)->nullable()->after('slug');
        });

        foreach ($this->defaults as $slug => $anchor) {
            DB::table('landing_sections')
                ->where('slug', $slug)
                ->update(['anchor' => $anchor]);
        }
    }

    public function down(): void
    {
        Schema::table('landing_sections', function (Blueprint $table) {
            $table->dropColumn('anchor');
        });
    }
};
