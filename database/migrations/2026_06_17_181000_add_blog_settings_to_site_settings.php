<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table): void {
            $table->string('blog_kicker')->nullable()->after('llms_txt_extra');
            $table->string('blog_title')->nullable()->after('blog_kicker');
            $table->text('blog_description')->nullable()->after('blog_title');
        });

        DB::table('site_settings')
            ->whereNull('blog_title')
            ->update([
                'blog_kicker' => 'Блог 24Logist',
                'blog_title' => 'Практика цифровой логистики',
                'blog_description' => 'Разбираем перевозки, автоматизацию, документооборот, контроль рейсов и управленческие процессы без лишней теории.',
            ]);
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table): void {
            $table->dropColumn([
                'blog_kicker',
                'blog_title',
                'blog_description',
            ]);
        });
    }
};
