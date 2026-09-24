<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('etrn_roulette_players', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('community_user_id')->unique()->constrained('community_users')->cascadeOnDelete();
            $table->unsignedBigInteger('attempts')->default(0);
            $table->timestamp('joined_at');
            $table->timestamp('last_played_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('etrn_roulette_players');
    }
};
