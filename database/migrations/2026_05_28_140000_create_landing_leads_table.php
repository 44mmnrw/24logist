<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('landing_leads', function (Blueprint $table) {
            $table->id();
            $table->string('type', 32)->index();
            $table->string('status', 32)->default('new')->index();
            $table->string('name');
            $table->string('phone', 64);
            $table->string('email')->nullable();
            $table->text('message')->nullable();
            $table->json('quiz_answers')->nullable();
            $table->string('source_url')->nullable();
            $table->string('ip', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('landing_leads');
    }
};
