<?php

use App\Models\SiteSetting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $row = DB::table('site_settings')->where('id', 1)->first();

        if ($row === null) {
            return;
        }

        $updates = [];

        if (blank($row->ai_site_summary ?? null)) {
            $updates['ai_site_summary'] = SiteSetting::defaultAiSiteSummary();
        }

        if (blank($row->llms_txt_extra ?? null)) {
            $updates['llms_txt_extra'] = SiteSetting::defaultLlmsTxtExtra();
        }

        if ($updates !== []) {
            $updates['updated_at'] = now();
            DB::table('site_settings')->where('id', 1)->update($updates);
        }
    }

    public function down(): void
    {
        // Не откатываем контент — пользователь мог уже отредактировать тексты.
    }
};
