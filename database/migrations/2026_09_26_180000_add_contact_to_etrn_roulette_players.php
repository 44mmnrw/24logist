<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('etrn_roulette_players', function (Blueprint $table): void {
            $table->string('contact_email')->nullable();
            $table->timestamp('contact_verified_at')->nullable();
            $table->timestamp('contact_consent_at')->nullable();
            $table->ipAddress('contact_consent_ip')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('etrn_roulette_players', function (Blueprint $table): void {
            $table->dropColumn(['contact_email', 'contact_verified_at', 'contact_consent_at', 'contact_consent_ip']);
        });
    }
};
