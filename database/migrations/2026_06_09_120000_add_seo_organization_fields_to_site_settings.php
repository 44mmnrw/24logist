<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->string('seo_meta_title')->nullable()->after('og_image_path');
            $table->text('seo_keywords')->nullable()->after('seo_meta_title');

            $table->string('org_brand_name')->nullable()->after('seo_keywords');
            $table->string('org_legal_name')->nullable()->after('org_brand_name');
            $table->string('org_email')->nullable()->after('org_legal_name');
            $table->string('org_phone')->nullable()->after('org_email');
            $table->string('org_logo_path')->nullable()->after('org_phone');
            $table->string('org_street_address')->nullable()->after('org_logo_path');
            $table->string('org_address_locality')->nullable()->after('org_street_address');
            $table->string('org_address_region')->nullable()->after('org_address_locality');
            $table->string('org_postal_code')->nullable()->after('org_address_region');
            $table->string('org_address_country', 2)->nullable()->default('RU')->after('org_postal_code');
            $table->string('org_inn', 12)->nullable()->after('org_address_country');
            $table->string('org_ogrn', 15)->nullable()->after('org_inn');
            $table->text('org_same_as')->nullable()->after('org_ogrn');

            $table->string('twitter_site')->nullable()->after('org_same_as');
            $table->string('twitter_creator')->nullable()->after('twitter_site');

            $table->string('google_site_verification')->nullable()->after('twitter_creator');
            $table->string('yandex_site_verification')->nullable()->after('google_site_verification');

            $table->text('ai_site_summary')->nullable()->after('yandex_site_verification');
            $table->text('llms_txt_extra')->nullable()->after('ai_site_summary');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn([
                'seo_meta_title',
                'seo_keywords',
                'org_brand_name',
                'org_legal_name',
                'org_email',
                'org_phone',
                'org_logo_path',
                'org_street_address',
                'org_address_locality',
                'org_address_region',
                'org_postal_code',
                'org_address_country',
                'org_inn',
                'org_ogrn',
                'org_same_as',
                'twitter_site',
                'twitter_creator',
                'google_site_verification',
                'yandex_site_verification',
                'ai_site_summary',
                'llms_txt_extra',
            ]);
        });
    }
};
