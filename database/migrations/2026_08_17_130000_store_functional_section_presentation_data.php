<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $section = DB::table('landing_sections')->where('slug', 'why')->first();

        if ($section) {
            DB::table('landing_sections')->where('slug', 'why')->update([
                'extra' => $this->encode(array_replace([
                    'quote' => 'Наша главная ценность в том, что мы упрощаем процесс работы с заявкой от Заказчика, делаем его простым и удобным',
                    'quote_initials' => 'СА',
                    'quote_author' => 'Станислав Аристов',
                    'quote_handle' => '@sss_air',
                ], $this->decode($section->extra))),
                'updated_at' => now(),
            ]);
        }

        foreach ([
            1 => 'images/functional/request.svg',
            2 => 'images/functional/payments.svg',
            3 => 'images/functional/expenses.svg',
            4 => 'images/functional/epd.svg',
            5 => 'images/functional/profit.svg',
            6 => 'images/functional/security.svg',
        ] as $sortOrder => $iconAsset) {
            $card = DB::table('landing_blocks')
                ->where('section_slug', 'why')
                ->where('block_type', 'card')
                ->where('sort_order', $sortOrder)
                ->first();

            if (! $card) {
                continue;
            }

            DB::table('landing_blocks')->where('id', $card->id)->update([
                'extra' => $this->encode(array_replace(
                    ['icon_asset' => $iconAsset],
                    $this->decode($card->extra),
                )),
                'updated_at' => now(),
            ]);
        }

        $this->clearCache();
    }

    public function down(): void
    {
        $cards = DB::table('landing_blocks')
            ->where('section_slug', 'why')
            ->where('block_type', 'card')
            ->get();

        foreach ($cards as $card) {
            $extra = $this->decode($card->extra);
            unset($extra['icon_asset']);

            DB::table('landing_blocks')->where('id', $card->id)->update([
                'extra' => $extra === [] ? null : $this->encode($extra),
                'updated_at' => now(),
            ]);
        }

        $this->clearCache();
    }

    /** @return array<string, mixed> */
    private function decode(mixed $value): array
    {
        if (! is_string($value) || $value === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }

    /** @param array<string, mixed> $value */
    private function encode(array $value): string
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    private function clearCache(): void
    {
        Cache::forget('landing.page.content.v3');
        Cache::forget('landing.page.content');
    }
};
