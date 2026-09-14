<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('community_reactions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('community_user_id')->constrained()->cascadeOnDelete();
            $table->string('target_type', 20);
            $table->unsignedBigInteger('target_id');
            $table->string('code', 30);
            $table->timestamps();
            $table->unique(['community_user_id', 'target_type', 'target_id'], 'community_reaction_one_per_target');
            $table->index(['target_type', 'target_id', 'code'], 'community_reaction_counts');
        });

        Schema::create('community_awards', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('community_user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('target_type', 20);
            $table->unsignedBigInteger('target_id');
            $table->string('code', 30);
            $table->string('message', 100)->nullable();
            $table->boolean('is_anonymous')->default(false);
            $table->timestamps();
            $table->unique(['community_user_id', 'target_type', 'target_id'], 'community_award_one_per_target');
            $table->index(['target_type', 'target_id', 'code'], 'community_award_counts');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('community_awards');
        Schema::dropIfExists('community_reactions');
    }
};
