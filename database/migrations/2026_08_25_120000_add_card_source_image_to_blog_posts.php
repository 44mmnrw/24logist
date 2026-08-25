<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('blog_posts', function (Blueprint $table): void {
            $table->string('card_source_image_path')->nullable()->after('cover_image_path');
        });

        DB::table('blog_posts')
            ->whereNotNull('card_image_path')
            ->where('card_image_path', '!=', '')
            ->where('card_image_path', 'not like', 'blog/cards/generated/%')
            ->orderBy('id')
            ->eachById(function (object $post): void {
                DB::table('blog_posts')
                    ->where('id', $post->id)
                    ->update(['card_source_image_path' => $post->card_image_path]);
            });
    }

    public function down(): void
    {
        Schema::table('blog_posts', function (Blueprint $table): void {
            $table->dropColumn('card_source_image_path');
        });
    }
};
