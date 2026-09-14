<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('community_posts', function (Blueprint $table): void {
            $table->foreignId('accepted_comment_id')->nullable()->after('comments_count')->constrained('community_comments')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable()->after('accepted_comment_id');
        });
    }

    public function down(): void
    {
        Schema::table('community_posts', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('accepted_comment_id');
            $table->dropColumn('resolved_at');
        });
    }
};
