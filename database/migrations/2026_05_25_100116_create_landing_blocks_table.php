<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('landing_blocks', function (Blueprint $table) {
            $table->id();
            $table->string('section_slug')->index();
            $table->string('block_type')->index();
            $table->foreignId('parent_id')->nullable()->constrained('landing_blocks')->nullOnDelete();
            $table->string('title')->nullable();
            $table->string('subtitle')->nullable();
            $table->text('description')->nullable();
            $table->string('icon')->nullable();
            $table->string('price')->nullable();
            $table->string('tag')->nullable();
            $table->string('link')->nullable();
            $table->string('button_text')->nullable();
            $table->string('button_style')->nullable();
            $table->json('extra')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_highlighted')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('landing_blocks');
    }
};
