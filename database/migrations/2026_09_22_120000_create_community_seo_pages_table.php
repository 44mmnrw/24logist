<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('community_seo_pages', function (Blueprint $table): void {
            $table->id();
            $table->string('page_key')->unique();
            $table->string('kind', 20)->index();
            $table->string('label');
            $table->string('path', 1000);
            $table->boolean('is_public')->default(true);
            $table->json('settings')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('community_seo_pages');
    }
};
