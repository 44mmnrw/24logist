<?php

namespace App\Support;

use App\Models\LandingSection;
use Illuminate\Support\Collection;

final class LandingSectionAnchor
{
    /** @var array<string, string> */
    private const DEFAULTS = [
        'hero' => 'hero',
        'features' => 'features',
        'why' => 'why',
        'pricing' => 'pricing',
        'quiz' => 'quiz',
        'faq' => 'faq',
        'final_cta' => 'final-cta',
    ];

    /** @var array<string, true> */
    private const WITHOUT_ANCHOR = [
        'header' => true,
        'footer' => true,
    ];

    public static function supports(?LandingSection $section): bool
    {
        if ($section === null) {
            return false;
        }

        return ! isset(self::WITHOUT_ANCHOR[$section->slug]);
    }

    public static function normalize(?string $anchor): ?string
    {
        $anchor = trim((string) ($anchor ?? ''));

        if ($anchor === '') {
            return null;
        }

        $anchor = ltrim($anchor, '#');
        $anchor = ltrim($anchor, '/');
        $anchor = preg_replace('/[^a-z0-9_-]/i', '', $anchor) ?? '';

        return $anchor !== '' ? strtolower($anchor) : null;
    }

    public static function id(?LandingSection $section): ?string
    {
        if ($section === null || ! self::supports($section)) {
            return null;
        }

        $anchor = self::normalize($section->anchor);

        if ($anchor !== null) {
            return $anchor;
        }

        return self::DEFAULTS[$section->slug] ?? null;
    }

    public static function hash(?LandingSection $section): ?string
    {
        $id = self::id($section);

        return $id !== null ? '#'.$id : null;
    }

    public static function pageLink(?LandingSection $section): ?string
    {
        $hash = self::hash($section);

        return $hash !== null ? '/'.$hash : null;
    }

    /**
     * @return array<string, string>
     */
    public static function linkSelectOptions(): array
    {
        return self::anchoredSections()
            ->mapWithKeys(function (LandingSection $section): array {
                $hash = self::hash($section);

                if ($hash === null) {
                    return [];
                }

                return [$hash => $section->name.' ('.$hash.')'];
            })
            ->all();
    }

    public static function adminHint(): string
    {
        $links = self::anchoredSections()
            ->map(function (LandingSection $section): ?string {
                $link = self::pageLink($section);

                return $link !== null ? $link : null;
            })
            ->filter()
            ->values()
            ->join(', ');

        if ($links === '') {
            return 'Якоря секций настраиваются в карточке каждой секции.';
        }

        return 'Якоря секций: '.$links.'. Меняются в поле «Якорь секции» у каждой секции.';
    }

    /**
     * @return Collection<int, LandingSection>
     */
    private static function anchoredSections(): Collection
    {
        return LandingSection::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->filter(fn (LandingSection $section): bool => self::id($section) !== null);
    }
}
