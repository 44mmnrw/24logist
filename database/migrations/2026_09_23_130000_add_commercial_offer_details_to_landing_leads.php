<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('landing_leads', function (Blueprint $table): void {
            $table->json('offer_details')->nullable();
            $table->timestamp('offer_sent_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('landing_leads', function (Blueprint $table): void {
            $table->dropColumn(['offer_details', 'offer_sent_at']);
        });
    }
};
