<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('etrn_roulette_spins', function (Blueprint $table): void {
            $table->id();
            $table->uuid('request_id')->unique();
            $table->char('actor_key', 64)->index();
            $table->foreignId('community_user_id')->nullable()->constrained('community_users')->nullOnDelete();
            $table->foreignId('etrn_roulette_player_id')->nullable()->constrained('etrn_roulette_players')->nullOnDelete();
            $table->boolean('is_jackpot')->default(false)->index();
            $table->json('outcome');
            $table->timestamps();

            $table->index(['is_jackpot', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('etrn_roulette_spins');
    }
};
