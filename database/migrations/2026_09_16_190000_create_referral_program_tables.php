<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('referral_program_settings', function (Blueprint $table): void {
            $table->id();
            $table->boolean('is_enabled')->default(false);
            $table->boolean('public_placements_enabled')->default(false);
            $table->unsignedSmallInteger('attribution_days')->default(90);
            $table->string('offer_version')->nullable();
            $table->string('offer_url', 2048)->nullable();
            $table->timestamps();
        });

        Schema::create('referral_terms', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('status')->default('draft')->index();
            $table->unsignedSmallInteger('commission_bps');
            $table->unsignedSmallInteger('commission_months')->default(12);
            $table->unsignedSmallInteger('invitee_discount_bps')->default(0);
            $table->unsignedSmallInteger('hold_days')->default(30);
            $table->unsignedBigInteger('minimum_payout_minor')->default(500000);
            $table->timestamp('starts_at')->nullable()->index();
            $table->timestamp('ends_at')->nullable()->index();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });

        Schema::create('referral_participants', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('terms_id')->nullable()->constrained('referral_terms')->nullOnDelete();
            $table->string('external_account_id')->unique();
            $table->string('company_name');
            $table->string('inn', 12)->unique();
            $table->string('contact_email');
            $table->string('code', 32)->unique();
            $table->string('status')->default('pending')->index();
            $table->unsignedSmallInteger('commission_bps_override')->nullable();
            $table->unsignedSmallInteger('commission_months_override')->nullable();
            $table->unsignedSmallInteger('invitee_discount_bps_override')->nullable();
            $table->unsignedSmallInteger('hold_days_override')->nullable();
            $table->unsignedBigInteger('minimum_payout_minor_override')->nullable();
            $table->string('offer_version')->nullable();
            $table->timestamp('offer_accepted_at')->nullable();
            $table->timestamp('bank_details_verified_at')->nullable();
            $table->text('bank_details')->nullable();
            $table->timestamp('suspended_at')->nullable();
            $table->text('suspension_reason')->nullable();
            $table->timestamps();
        });

        Schema::create('referral_placements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('participant_id')->constrained('referral_participants')->cascadeOnDelete();
            $table->string('code', 32)->unique();
            $table->string('status')->default('draft')->index();
            $table->string('platform');
            $table->string('placement_url', 2048);
            $table->text('creative_text');
            $table->string('erid')->nullable()->index();
            $table->string('ord_name')->nullable();
            $table->string('contract_number')->nullable();
            $table->string('act_number')->nullable();
            $table->bigInteger('placement_cost_minor')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('referral_attributions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('participant_id')->constrained('referral_participants')->restrictOnDelete();
            $table->foreignId('placement_id')->nullable()->constrained('referral_placements')->nullOnDelete();
            $table->foreignId('terms_id')->constrained('referral_terms')->restrictOnDelete();
            $table->string('external_registration_id')->nullable()->unique();
            $table->string('external_account_id')->nullable()->unique();
            $table->string('source_type')->default('personal');
            $table->string('referral_code', 32);
            $table->string('referred_company_name')->nullable();
            $table->string('referred_inn', 12)->unique();
            $table->string('referred_email')->nullable();
            $table->string('status')->default('pending')->index();
            $table->text('rejection_reason')->nullable();
            $table->unsignedSmallInteger('commission_bps');
            $table->unsignedSmallInteger('commission_months');
            $table->unsignedSmallInteger('invitee_discount_bps');
            $table->unsignedSmallInteger('hold_days');
            $table->unsignedBigInteger('minimum_payout_minor');
            $table->timestamp('attributed_at');
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('first_paid_at')->nullable();
            $table->timestamp('commission_ends_at')->nullable();
            $table->timestamp('discount_used_at')->nullable();
            $table->timestamps();
        });

        Schema::create('referral_clicks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('participant_id')->constrained('referral_participants')->cascadeOnDelete();
            $table->foreignId('placement_id')->nullable()->constrained('referral_placements')->nullOnDelete();
            $table->string('referral_code', 32);
            $table->char('ip_hash', 64)->nullable();
            $table->char('user_agent_hash', 64)->nullable();
            $table->timestamp('visited_at')->index();
        });

        Schema::create('referral_payouts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('participant_id')->constrained('referral_participants')->restrictOnDelete();
            $table->date('period_start');
            $table->date('period_end');
            $table->bigInteger('amount_minor')->default(0);
            $table->string('currency', 3)->default('RUB');
            $table->string('status')->default('draft')->index();
            $table->string('payment_reference')->nullable();
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });

        Schema::create('referral_commissions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('attribution_id')->constrained('referral_attributions')->restrictOnDelete();
            $table->foreignId('payout_id')->nullable()->constrained('referral_payouts')->nullOnDelete();
            $table->string('external_payment_id')->nullable()->index();
            $table->string('event_key')->unique();
            $table->string('type')->default('commission');
            $table->string('status')->default('pending')->index();
            $table->unsignedBigInteger('base_minor')->default(0);
            $table->unsignedSmallInteger('rate_bps')->default(0);
            $table->bigInteger('amount_minor');
            $table->timestamp('available_at')->nullable()->index();
            $table->timestamp('paid_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('referral_audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action')->index();
            $table->string('target_type');
            $table->string('target_id')->nullable();
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->timestamp('created_at');
        });

        DB::table('referral_program_settings')->insert([
            'id' => 1,
            'is_enabled' => false,
            'public_placements_enabled' => false,
            'attribution_days' => 90,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('referral_audit_logs');
        Schema::dropIfExists('referral_commissions');
        Schema::dropIfExists('referral_payouts');
        Schema::dropIfExists('referral_clicks');
        Schema::dropIfExists('referral_attributions');
        Schema::dropIfExists('referral_placements');
        Schema::dropIfExists('referral_participants');
        Schema::dropIfExists('referral_terms');
        Schema::dropIfExists('referral_program_settings');
    }
};
