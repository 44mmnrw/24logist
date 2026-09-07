<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table): void {
            $table->boolean('cabinet_login_enabled')->default(false)->after('route_api_timeout');
            $table->string('cabinet_login_button_text')->default('Войти в личный кабинет')->after('cabinet_login_enabled');
            $table->string('cabinet_login_button_style')->default('ghost')->after('cabinet_login_button_text');
            $table->boolean('cabinet_registration_enabled')->default(false)->after('cabinet_login_button_style');
            $table->string('cabinet_registration_button_text')->default('Создать личный кабинет')->after('cabinet_registration_enabled');
            $table->string('cabinet_registration_button_style')->default('primary')->after('cabinet_registration_button_text');
            $table->string('cabinet_login_eyebrow')->default('ЛогистРу')->after('cabinet_registration_button_style');
            $table->string('cabinet_login_modal_title')->default('Вход в личный кабинет')->after('cabinet_login_eyebrow');
            $table->text('cabinet_login_modal_description')->nullable()->after('cabinet_login_modal_title');
            $table->string('cabinet_login_identifier_label')->default('Email')->after('cabinet_login_modal_description');
            $table->string('cabinet_login_password_label')->default('Пароль')->after('cabinet_login_identifier_label');
            $table->string('cabinet_login_submit_text')->default('Войти')->after('cabinet_login_password_label');
            $table->text('cabinet_login_url')->nullable()->after('cabinet_login_submit_text');
            $table->string('cabinet_login_forgot_text')->default('Забыли пароль?')->after('cabinet_login_url');
            $table->text('cabinet_login_forgot_url')->nullable()->after('cabinet_login_forgot_text');
            $table->string('cabinet_login_origin')->nullable()->after('cabinet_login_forgot_url');
            $table->string('cabinet_login_connect_ip')->nullable()->after('cabinet_login_origin');
            $table->text('cabinet_login_api_secret')->nullable()->after('cabinet_login_connect_ip');
            $table->unsignedTinyInteger('cabinet_login_api_timeout')->default(15)->after('cabinet_login_api_secret');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table): void {
            $table->dropColumn([
                'cabinet_login_enabled',
                'cabinet_login_button_text',
                'cabinet_login_button_style',
                'cabinet_registration_enabled',
                'cabinet_registration_button_text',
                'cabinet_registration_button_style',
                'cabinet_login_eyebrow',
                'cabinet_login_modal_title',
                'cabinet_login_modal_description',
                'cabinet_login_identifier_label',
                'cabinet_login_password_label',
                'cabinet_login_submit_text',
                'cabinet_login_url',
                'cabinet_login_forgot_text',
                'cabinet_login_forgot_url',
                'cabinet_login_origin',
                'cabinet_login_connect_ip',
                'cabinet_login_api_secret',
                'cabinet_login_api_timeout',
            ]);
        });
    }
};
