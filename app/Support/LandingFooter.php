<?php

namespace App\Support;

use App\Models\LandingBlock;
use Illuminate\Support\Arr;

final class LandingFooter
{
    /**
     * @param  array<int, array{title?: string|null, link?: string|null, icon?: string|null}>  $links
     */
    public static function syncLinks(LandingBlock $column, array $links): void
    {
        $column->children()
            ->where('block_type', 'footer_link')
            ->delete();

        foreach ($links as $index => $link) {
            $title = trim((string) ($link['title'] ?? ''));

            if ($title === '') {
                continue;
            }

            $icon = filled($link['icon'] ?? null)
                ? LandingIcons::toStorage((string) $link['icon'])
                : null;

            LandingBlock::query()->create([
                'section_slug' => $column->section_slug,
                'block_type' => 'footer_link',
                'parent_id' => $column->id,
                'title' => $title,
                'link' => trim((string) ($link['link'] ?? '')) ?: null,
                'icon' => $icon,
                'sort_order' => $index + 1,
                'is_active' => true,
            ]);
        }
    }

    /**
     * @return array<int, array{title: string, link: ?string, icon: ?string}>
     */
    public static function linksFormState(LandingBlock $column): array
    {
        return $column->children()
            ->where('block_type', 'footer_link')
            ->orderBy('sort_order')
            ->get()
            ->map(fn (LandingBlock $link): array => [
                'title' => $link->title ?? '',
                'link' => $link->link,
                'icon' => LandingIcons::resolve($link->icon),
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function stripVirtualFields(array $data): array
    {
        return Arr::except($data, ['footer_links']);
    }
}
