<?php

namespace App\Support;

use App\Models\LandingBlock;
use Illuminate\Support\Arr;

final class LandingPricing
{
    /**
     * @param  array<int, array{title?: string|null, icon?: string|null}>  $features
     */
    public static function syncFeatures(LandingBlock $plan, array $features): void
    {
        $plan->children()
            ->where('block_type', 'feature')
            ->delete();

        foreach ($features as $index => $feature) {
            $title = trim((string) ($feature['title'] ?? ''));

            if ($title === '') {
                continue;
            }

            $iconKey = filled($feature['icon'] ?? null)
                ? (string) $feature['icon']
                : 'check';

            LandingBlock::query()->create([
                'section_slug' => $plan->section_slug,
                'block_type' => 'feature',
                'parent_id' => $plan->id,
                'title' => $title,
                'icon' => LandingIcons::normalize($iconKey),
                'sort_order' => $index + 1,
                'is_active' => true,
            ]);
        }
    }

    /**
     * @return array<int, array{title: string, icon: ?string}>
     */
    public static function featuresFormState(LandingBlock $plan): array
    {
        return $plan->children()
            ->where('block_type', 'feature')
            ->orderBy('sort_order')
            ->get()
            ->map(fn (LandingBlock $feature): array => [
                'title' => $feature->title ?? '',
                'icon' => LandingIcons::resolve($feature->icon),
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
        return Arr::except($data, ['plan_features']);
    }
}
