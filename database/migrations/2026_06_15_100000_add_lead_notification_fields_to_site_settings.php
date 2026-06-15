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
            $table->boolean('leads_notifications_enabled')->default(true)->after('llms_txt_extra');
            $table->text('leads_notification_emails')->nullable()->after('leads_notifications_enabled');
        });

        DB::table('site_settings')->where('id', 1)->update([
            'leads_notifications_enabled' => true,
            'leads_notification_emails' => 'info@24logist.ru',
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn([
                'leads_notifications_enabled',
                'leads_notification_emails',
            ]);
        });
    }
};
