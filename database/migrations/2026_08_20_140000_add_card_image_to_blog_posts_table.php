<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('blog_posts', function (Blueprint $table): void {
            $table->string('card_image_path')->nullable()->after('cover_image_path');
            $table->boolean('show_card_logo')->default(true)->after('card_image_path');
        });
    }

    public function down(): void
    {
        Schema::table('blog_posts', function (Blueprint $table): void {
            $table->dropColumn(['card_image_path', 'show_card_logo']);
        });
    }
};
