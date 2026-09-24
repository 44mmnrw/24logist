<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_counters', function (Blueprint $table): void {
            $table->string('key')->primary();
            $table->unsignedBigInteger('attempts')->default(0);
            $table->timestamps();
        });

        DB::table('game_counters')->insert([
            'key' => 'etrn-roulette',
            'attempts' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('game_counters');
    }
};
