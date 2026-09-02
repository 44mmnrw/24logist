<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('community_login_challenges', function (Blueprint $table): void {
            $table->timestamp('return_sent_at')->nullable()->after('consumed_at');
            $table->timestamp('return_consumed_at')->nullable()->after('return_sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('community_login_challenges', function (Blueprint $table): void {
            $table->dropColumn(['return_sent_at', 'return_consumed_at']);
        });
    }
};
