<?php

namespace App\Support;

use App\Models\LandingBlock;
use Illuminate\Support\Arr;

final class LandingQuiz
{
    /**
     * @param  array<int, array{title?: string|null, recommended_plan_id?: int|string|null}>  $options
     */
    public static function syncOptions(LandingBlock $question, array $options): void
    {
        $question->children()
            ->where('block_type', 'option')
            ->delete();

        foreach ($options as $index => $option) {
            $title = trim((string) ($option['title'] ?? ''));

            if ($title === '') {
                continue;
            }

            $extra = null;
            $planId = (int) ($option['recommended_plan_id'] ?? 0);

            if ($planId > 0) {
                $extra = ['recommended_plan_id' => $planId];
            }

            LandingBlock::query()->create([
                'section_slug' => $question->section_slug,
                'block_type' => 'option',
                'parent_id' => $question->id,
                'title' => $title,
                'sort_order' => $index + 1,
                'is_active' => true,
                'extra' => $extra,
            ]);
        }
    }

    /**
     * @return array<int, array{title: string, recommended_plan_id: ?int}>
     */
    public static function optionsFormState(LandingBlock $question): array
    {
        return $question->children()
            ->where('block_type', 'option')
            ->orderBy('sort_order')
            ->get()
            ->map(fn (LandingBlock $option): array => [
                'title' => $option->title ?? '',
                'recommended_plan_id' => filled($option->extra['recommended_plan_id'] ?? null)
                    ? (int) $option->extra['recommended_plan_id']
                    : null,
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
        return Arr::except($data, ['quiz_options']);
    }
}
