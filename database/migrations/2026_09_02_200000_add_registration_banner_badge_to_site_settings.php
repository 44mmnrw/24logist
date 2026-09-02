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
            $table->string('epd_popup_registration_badge_value', 30)->nullable()->after('epd_popup_registration_image_alt');
            $table->string('epd_popup_registration_badge_label', 100)->nullable()->after('epd_popup_registration_badge_value');
        });

        DB::table('site_settings')->update([
            'epd_popup_registration_badge_value' => '−50%',
            'epd_popup_registration_badge_label' => 'на пакет ЭПД',
        ]);
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table): void {
            $table->dropColumn([
                'epd_popup_registration_badge_value',
                'epd_popup_registration_badge_label',
            ]);
        });
    }
};
