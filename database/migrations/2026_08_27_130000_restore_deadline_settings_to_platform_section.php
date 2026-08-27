<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const DEADLINE_KEYS = [
        'deadline_kicker',
        'deadline_date',
        'deadline_icon',
        'deadline_text',
        'deadline_button_text',
    ];

    public function up(): void
    {
        $this->moveSettings('why', 'platform');
    }

    public function down(): void
    {
        $this->moveSettings('platform', 'why');
    }

    private function moveSettings(string $fromSlug, string $toSlug): void
    {
        $from = DB::table('landing_sections')->where('slug', $fromSlug)->first();
        $to = DB::table('landing_sections')->where('slug', $toSlug)->first();

        if (! $from || ! $to) {
            return;
        }

        $fromExtra = $this->decodeExtra($from->extra);
        $toExtra = $this->decodeExtra($to->extra);

        foreach (self::DEADLINE_KEYS as $key) {
            if (array_key_exists($key, $fromExtra)) {
                $toExtra[$key] = $fromExtra[$key];
                unset($fromExtra[$key]);
            }
        }

        DB::table('landing_sections')->where('slug', $toSlug)->update([
            'extra' => json_encode($toExtra, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'updated_at' => now(),
        ]);

        DB::table('landing_sections')->where('slug', $fromSlug)->update([
            'extra' => json_encode($fromExtra, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'updated_at' => now(),
        ]);
    }

    private function decodeExtra(mixed $extra): array
    {
        if (is_array($extra)) {
            return $extra;
        }

        $decoded = json_decode((string) $extra, true);

        return is_array($decoded) ? $decoded : [];
    }
};
