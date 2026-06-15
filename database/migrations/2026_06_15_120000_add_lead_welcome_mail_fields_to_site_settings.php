<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->boolean('leads_welcome_enabled')->default(true)->after('leads_notification_emails');
            $table->string('leads_welcome_subject')->nullable()->after('leads_welcome_enabled');
            $table->text('leads_welcome_body')->nullable()->after('leads_welcome_subject');
        });

        DB::table('site_settings')->where('id', 1)->update([
            'leads_welcome_enabled' => true,
            'leads_welcome_subject' => 'Спасибо за заявку, {name}!',
            'leads_welcome_body' => <<<'TEXT'
Здравствуйте, {name}!

Мы получили вашу заявку на сайте {brand}. Менеджер свяжется с вами в рабочее время.

Ваш телефон: {phone}
Тип заявки: {type}

С уважением,
Команда {brand}
{company_email} · {company_phone}
TEXT,
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn([
                'leads_welcome_enabled',
                'leads_welcome_subject',
                'leads_welcome_body',
            ]);
        });
    }
};
