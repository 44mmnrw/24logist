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
            $table->string('mail_host')->nullable()->after('leads_notification_emails');
            $table->unsignedSmallInteger('mail_port')->default(465)->after('mail_host');
            $table->string('mail_encryption', 16)->default('smtps')->after('mail_port');
            $table->string('mail_username')->nullable()->after('mail_encryption');
            $table->text('mail_password')->nullable()->after('mail_username');
            $table->string('mail_from_address')->nullable()->after('mail_password');
            $table->string('mail_from_name')->nullable()->after('mail_from_address');
        });

        DB::table('site_settings')->where('id', 1)->update([
            'mail_port' => 465,
            'mail_encryption' => 'smtps',
            'mail_from_address' => 'info@24logist.ru',
            'mail_from_name' => 'ЛогистРу',
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn([
                'mail_host',
                'mail_port',
                'mail_encryption',
                'mail_username',
                'mail_password',
                'mail_from_address',
                'mail_from_name',
            ]);
        });
    }
};
