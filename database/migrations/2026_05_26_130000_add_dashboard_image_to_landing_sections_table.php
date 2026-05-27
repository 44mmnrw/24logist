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
            $table->string('dashboard_image')->nullable()->after('button_secondary_url');
        });

        LandingSection::query()->each(function (LandingSection $section): void {
            $path = LandingMedia::normalizePath($section->extra['dashboard_image'] ?? null);

            if ($path === null) {
                return;
            }

            $extra = $section->extra;
            unset($extra['dashboard_image']);

            $section->update([
                'dashboard_image' => $path,
                'extra' => $extra,
            ]);
        });
    }

    public function down(): void
    {
        LandingSection::query()
            ->whereNotNull('dashboard_image')
            ->each(function (LandingSection $section): void {
                $extra = $section->extra ?? [];
                $extra['dashboard_image'] = $section->dashboard_image;

                $section->update([
                    'extra' => $extra,
                    'dashboard_image' => null,
                ]);
            });

        Schema::table('landing_sections', function (Blueprint $table) {
            $table->dropColumn('dashboard_image');
        });
    }
};
