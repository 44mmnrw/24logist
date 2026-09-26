<?php

use App\Services\SiteSettingsService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $old = 'ЛогистРу';
        $new = 'логистРу';
        $oldJson = trim((string) json_encode($old), '"');
        $newJson = trim((string) json_encode($new), '"');

        foreach ([
            'site_settings',
            'cms_pages',
            'landing_sections',
            'landing_blocks',
            'blog_posts',
            'blog_categories',
            'blog_tags',
            'community_seo_pages',
        ] as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            DB::table($table)->orderBy('id')->chunkById(100, function ($rows) use ($table, $old, $new, $oldJson, $newJson): void {
                foreach ($rows as $row) {
                    $updates = [];

                    foreach (get_object_vars($row) as $column => $value) {
                        if ($column === 'id' || ! is_string($value)) {
                            continue;
                        }

                        $updated = str_replace([$old, $oldJson], [$new, $newJson], $value);
                        if ($updated !== $value) {
                            $updates[$column] = $updated;
                        }
                    }

                    if ($updates !== []) {
                        DB::table($table)->where('id', $row->id)->update($updates);
                    }
                }
            });
        }

        app(SiteSettingsService::class)->clearCache();
    }

    public function down(): void
    {
        // Existing editorial content may be changed later; reverting its casing would overwrite those edits.
    }
};
