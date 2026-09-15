<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('community_users', function (Blueprint $table): void {
            $table->timestamp('last_login_at')->nullable()->after('terms_accepted_at')->index();
            $table->timestamp('last_seen_at')->nullable()->after('last_login_at')->index();
            $table->string('last_login_ip', 45)->nullable()->after('last_seen_at');
            $table->text('last_user_agent')->nullable()->after('last_login_ip');
        });

        Schema::create('community_user_sessions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('community_user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('community_identity_id')->nullable()->constrained('community_identities')->nullOnDelete();
            $table->char('session_id_hash', 64)->unique();
            $table->string('provider', 20)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('logged_in_at');
            $table->timestamp('last_seen_at')->index();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('logged_out_at')->nullable();
            $table->timestamps();
            $table->index(['community_user_id', 'last_seen_at'], 'cus_user_seen_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('community_user_sessions');
        Schema::table('community_users', function (Blueprint $table): void {
            $table->dropColumn(['last_login_at', 'last_seen_at', 'last_login_ip', 'last_user_agent']);
        });
    }
};
