<?php

use App\Models\LandingSection;
use App\Support\LandingMedia;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('landing_sections', function (Blueprint $table) {
            $table->string('mobile_image')->nullable()->after('dashboard_image');
        });

        LandingSection::query()->each(function (LandingSection $section): void {
            $path = LandingMedia::normalizePath($section->extra['mobile_image'] ?? null);

            if ($path === null) {
                return;
            }

            $extra = $section->extra;
            unset($extra['mobile_image']);

            $section->update([
                'mobile_image' => $path,
                'extra' => $extra,
            ]);
        });
    }

    public function down(): void
    {
        LandingSection::query()
            ->whereNotNull('mobile_image')
            ->each(function (LandingSection $section): void {
                $extra = $section->extra ?? [];
                $extra['mobile_image'] = $section->mobile_image;

                $section->update([
                    'extra' => $extra,
                    'mobile_image' => null,
                ]);
            });

        Schema::table('landing_sections', function (Blueprint $table) {
            $table->dropColumn('mobile_image');
        });
    }
};
