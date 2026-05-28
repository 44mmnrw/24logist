<?php

namespace App\Support;

use App\Models\LandingBlock;
use Illuminate\Support\Arr;

final class LandingPlatform
{
    /**
     * @param  array<int, array{title?: string|null, subtitle?: string|null}>  $roles
     */
    public static function syncRoles(LandingBlock $card, array $roles): void
    {
        $card->children()
            ->where('block_type', 'role')
            ->delete();

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
        return Arr::except($data, ['platform_roles']);
    }
}
