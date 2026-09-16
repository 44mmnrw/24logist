<?php

use App\Services\Community\CommunityKarmaService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('community_users', function (Blueprint $table): void {
            $table->boolean('show_karma')->default(true)->after('karma');
        });

        app(CommunityKarmaService::class)->recalculateMany(
            DB::table('community_users')->pluck('id'),
        );
    }

    public function down(): void
    {
        Schema::table('community_users', function (Blueprint $table): void {
            $table->dropColumn('show_karma');
        });
    }
};
