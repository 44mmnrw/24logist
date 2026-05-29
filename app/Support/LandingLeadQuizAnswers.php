<?php

namespace App\Support;

use App\Models\LandingBlock;

final class LandingLeadQuizAnswers
{
    /**
     * @param  array<int|string, int|string>  $answers  question_id => option_id
     * @return array<int, array{question: string, answer: string}>
     */
    public static function normalize(array $answers): array
    {
        $normalized = [];

        foreach ($answers as $questionId => $optionId) {
            $questionId = (int) $questionId;
            $optionId = (int) $optionId;

            if ($questionId <= 0 || $optionId <= 0) {
                continue;
            }

            $question = LandingBlock::query()
                ->where('id', $questionId)
                ->where('block_type', 'question')
                ->where('section_slug', 'quiz')
                ->first();

            if (! $question) {
                continue;
            }

            $option = LandingBlock::query()
                ->where('id', $optionId)
                ->where('block_type', 'option')
                ->where('parent_id', $question->id)
                ->first();

            if (! $option) {
                continue;
            }

            $normalized[] = [
                'question' => (string) $question->title,
                'answer' => (string) $option->title,
            ];
        }

        return $normalized;
    }
}
