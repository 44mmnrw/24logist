<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('community_login_challenges', function (Blueprint $table): void {
            $table->timestamp('prompt_sent_at')->nullable()->after('consumed_at');
        });
    }

    public function down(): void
    {
        Schema::table('community_login_challenges', function (Blueprint $table): void {
            $table->dropColumn('prompt_sent_at');
        });
    }
};
