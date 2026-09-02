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
            $table->string('epd_popup_registration_image_path', 500)->nullable()->after('epd_popup_registration_enabled');
            $table->string('epd_popup_registration_image_alt', 255)->nullable()->after('epd_popup_registration_image_path');
            $table->string('epd_popup_registration_eyebrow', 100)->nullable()->after('epd_popup_registration_image_alt');
            $table->string('epd_popup_registration_title')->nullable()->after('epd_popup_registration_eyebrow');
            $table->text('epd_popup_registration_description')->nullable()->after('epd_popup_registration_title');
            $table->string('epd_popup_registration_benefit_1')->nullable()->after('epd_popup_registration_description');
            $table->string('epd_popup_registration_benefit_2')->nullable()->after('epd_popup_registration_benefit_1');
            $table->string('epd_popup_registration_benefit_3')->nullable()->after('epd_popup_registration_benefit_2');
            $table->string('epd_popup_registration_button_text', 100)->nullable()->after('epd_popup_registration_benefit_3');
            $table->string('epd_popup_registration_button_url', 2048)->nullable()->after('epd_popup_registration_button_text');
        });

        DB::table('site_settings')->update([
            'epd_popup_registration_image_alt' => '14 дней тестового доступа, встроенный ЭДО и ЭПД, бесплатная настройка',
            'epd_popup_registration_eyebrow' => 'Специальное предложение',
            'epd_popup_registration_title' => '14 дней тестового доступа',
            'epd_popup_registration_description' => 'Создайте личный кабинет — бесплатно настроим систему и откроем тестовый доступ со встроенным ЭДО/ЭПД.',
            'epd_popup_registration_benefit_1' => '14 дней тестового доступа',
            'epd_popup_registration_benefit_2' => 'Встроенный ЭДО/ЭПД для работы с документами',
            'epd_popup_registration_benefit_3' => 'Бесплатная настройка системы',
            'epd_popup_registration_button_text' => 'Создать личный кабинет',
            'epd_popup_registration_button_url' => 'https://logistsystem.ru/register',
        ]);
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table): void {
            $table->dropColumn([
                'epd_popup_registration_image_path',
                'epd_popup_registration_image_alt',
                'epd_popup_registration_eyebrow',
                'epd_popup_registration_title',
                'epd_popup_registration_description',
                'epd_popup_registration_benefit_1',
                'epd_popup_registration_benefit_2',
                'epd_popup_registration_benefit_3',
                'epd_popup_registration_button_text',
                'epd_popup_registration_button_url',
            ]);
        });
    }
};
