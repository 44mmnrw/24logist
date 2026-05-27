<?php

namespace App\Support;

use App\Models\LandingBlock;
use Illuminate\Support\Arr;

final class LandingQuiz
{
    /**
     * @param  array<int, array{title?: string|null}>  $options
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

            LandingBlock::query()->create([
                'section_slug' => $question->section_slug,
                'block_type' => 'option',
                'parent_id' => $question->id,
                'title' => $title,
                'sort_order' => $index + 1,
                'is_active' => true,
            ]);
        }
    }

    /**
     * @return array<int, array{title: string}>
     */
    public static function optionsFormState(LandingBlock $question): array
    {
        return $question->children()
            ->where('block_type', 'option')
            ->orderBy('sort_order')
            ->get()
            ->map(fn (LandingBlock $option): array => ['title' => $option->title ?? ''])
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
