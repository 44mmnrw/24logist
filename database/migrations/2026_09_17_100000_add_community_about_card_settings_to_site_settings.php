<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table): void {
            $table->boolean('community_about_card_enabled')->default(true)->after('community_vk_enabled');
            $table->string('community_about_card_eyebrow', 80)->default('логистРу')->after('community_about_card_enabled');
            $table->string('community_about_card_title', 160)->default('Сообщество о логистике')->after('community_about_card_eyebrow');
            $table->string('community_about_card_description', 500)->default('Практические вопросы перевозчиков, экспедиторов, грузовладельцев и логистов.')->after('community_about_card_title');
            $table->string('community_about_card_members_label', 80)->default('участников')->after('community_about_card_description');
            $table->string('community_about_card_topics_label', 80)->default('обсуждений')->after('community_about_card_members_label');
            $table->string('community_about_card_button_text', 100)->default('Все обсуждения')->after('community_about_card_topics_label');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table): void {
            $table->dropColumn([
                'community_about_card_enabled',
                'community_about_card_eyebrow',
                'community_about_card_title',
                'community_about_card_description',
                'community_about_card_members_label',
                'community_about_card_topics_label',
                'community_about_card_button_text',
            ]);
        });
    }
};
