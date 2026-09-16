<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('referral_partner_applications', function (Blueprint $table): void {
            $table->id();
            $table->string('company_name');
            $table->string('inn', 12)->unique();
            $table->string('contact_name');
            $table->string('contact_email')->index();
            $table->string('contact_phone', 32);
            $table->string('status')->default('pending')->index();
            $table->text('review_note')->nullable();
            $table->boolean('terms_accepted')->default(false);
            $table->boolean('privacy_accepted')->default(false);
            $table->char('ip_hash', 64)->nullable();
            $table->char('user_agent_hash', 64)->nullable();
            $table->foreignId('participant_id')->nullable()->constrained('referral_participants')->nullOnDelete();
            $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referral_partner_applications');
    }
};
