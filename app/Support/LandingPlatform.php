<?php

namespace App\Support;

use App\Models\LandingBlock;
use Illuminate\Support\Arr;

final class LandingPlatform
{
    private const VIRTUAL_FIELDS = [
        'platform_note_text',
        'platform_note_icon',
        'platform_list_items',
        'platform_pills',
        'platform_roles',
    ];

    /**
     * @param  array<string, mixed>  $data
     */
    public static function syncContent(LandingBlock $card, array $data): void
    {
        $card->children()
            ->whereIn('block_type', ['note', 'list_item', 'pill', 'role'])
            ->delete();

        $noteText = trim((string) ($data['platform_note_text'] ?? ''));

        if ($noteText !== '') {
            LandingBlock::query()->create([
                'section_slug' => $card->section_slug,
                'block_type' => 'note',
                'parent_id' => $card->id,
                'description' => $noteText,
                'icon' => $data['platform_note_icon'] ?? null,
                'sort_order' => 1,
                'is_active' => true,
            ]);
        }

        self::syncTitledChildren($card, 'list_item', $data['platform_list_items'] ?? [], true);
        self::syncTitledChildren($card, 'pill', $data['platform_pills'] ?? []);
        self::createRoles($card, $data['platform_roles'] ?? []);
    }

    /**
     * @param  array<int, array{title?: string|null, subtitle?: string|null}>  $roles
     */
    public static function syncRoles(LandingBlock $card, array $roles): void
    {
        $card->children()
            ->where('block_type', 'role')
            ->delete();

        self::createRoles($card, $roles);
    }

    /**
     * @return array<string, mixed>
     */
    public static function contentFormState(LandingBlock $card): array
    {
        $children = $card->children()->orderBy('sort_order')->get();
        $note = $children->firstWhere('block_type', 'note');

        return [
            'platform_note_text' => $note?->description ?? '',
            'platform_note_icon' => $note?->icon,
            'platform_list_items' => $children
                ->where('block_type', 'list_item')
                ->map(fn (LandingBlock $item): array => [
                    'title' => $item->title ?? '',
                    'icon' => $item->icon,
                ])
                ->values()
                ->all(),
            'platform_pills' => $children
                ->where('block_type', 'pill')
                ->map(fn (LandingBlock $pill): array => ['title' => $pill->title ?? ''])
                ->values()
                ->all(),
            'platform_roles' => $children
                ->where('block_type', 'role')
                ->map(fn (LandingBlock $role): array => [
                    'title' => $role->title ?? '',
                    'subtitle' => $role->subtitle ?? '',
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * @param  array<int, array{title?: string|null, subtitle?: string|null}>  $roles
     */
    private static function createRoles(LandingBlock $card, array $roles): void
    {
        foreach ($roles as $index => $role) {
            $title = trim((string) ($role['title'] ?? ''));
            $subtitle = trim((string) ($role['subtitle'] ?? ''));

            if ($title === '' && $subtitle === '') {
                continue;
            }

            LandingBlock::query()->create([
                'section_slug' => $card->section_slug,
                'block_type' => 'role',
                'parent_id' => $card->id,
                'title' => $title,
                'subtitle' => $subtitle ?: null,
                'sort_order' => $index + 1,
                'is_active' => true,
            ]);
        }
    }

    /**
     * @param  array<int, array{title?: string|null, icon?: string|null}>  $items
     */
    private static function syncTitledChildren(
        LandingBlock $card,
        string $blockType,
        array $items,
        bool $withIcon = false,
    ): void {
        foreach ($items as $index => $item) {
            $title = trim((string) ($item['title'] ?? ''));

            if ($title === '') {
                continue;
            }

            LandingBlock::query()->create([
                'section_slug' => $card->section_slug,
                'block_type' => $blockType,
                'parent_id' => $card->id,
                'title' => $title,
                'icon' => $withIcon ? ($item['icon'] ?? null) : null,
                'sort_order' => $index + 1,
                'is_active' => true,
            ]);
        }
    }

    /**
     * @return array<int, array{title: string, subtitle: string}>
     */
    public static function rolesFormState(LandingBlock $card): array
    {
        return $card->children()
            ->where('block_type', 'role')
            ->orderBy('sort_order')
            ->get()
            ->map(fn (LandingBlock $role): array => [
                'title' => $role->title ?? '',
                'subtitle' => $role->subtitle ?? '',
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
        return Arr::except($data, self::VIRTUAL_FIELDS);
    }
}
