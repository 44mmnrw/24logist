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
            $table->string('leads_notification_subject')->nullable()->after('leads_notification_emails');
            $table->text('leads_notification_body')->nullable()->after('leads_notification_subject');
        });

        DB::table('site_settings')->where('id', 1)->update([
            'leads_notification_subject' => 'Новая заявка: {type} — {name}',
            'leads_notification_body' => <<<'TEXT'
Новая заявка с сайта {brand}

Тип: {type}
Имя: {name}
Телефон: {phone}
Email: {email}
Тариф: {plan}

Открыть в админке: {admin_url}
TEXT,
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn([
                'leads_notification_subject',
                'leads_notification_body',
            ]);
        });
    }
};
