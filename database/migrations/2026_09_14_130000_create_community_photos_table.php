<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('community_photos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('community_post_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('community_comment_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('path', 255)->unique();
            $table->unsignedSmallInteger('width');
            $table->unsignedSmallInteger('height');
            $table->unsignedTinyInteger('position')->default(0);
            $table->timestamps();
            $table->index(['community_post_id', 'position']);
            $table->index(['community_comment_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('community_photos');
    }
};
