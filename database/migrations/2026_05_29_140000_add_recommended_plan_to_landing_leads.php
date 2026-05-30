<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('landing_leads', function (Blueprint $table) {
            $table->unsignedBigInteger('recommended_plan_id')->nullable()->after('quiz_answers');
            $table->string('recommended_plan_title')->nullable()->after('recommended_plan_id');
        });
    }

    public function down(): void
    {
        Schema::table('landing_leads', function (Blueprint $table) {
            $table->dropColumn(['recommended_plan_id', 'recommended_plan_title']);
        });
    }
};
