<?php

namespace App\Support;

use App\Models\LandingBlock;
use Illuminate\Support\Collection;

final class LandingQuizRecommendation
{
    /**
     * @return array<int, string>
     */
    public static function planSelectOptions(): array
    {
        return LandingBlock::query()
            ->where('section_slug', 'pricing')
            ->where('block_type', 'plan')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->pluck('title', 'id')
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function plansPayload(): array
    {
        $plans = [];

        foreach (self::pricingPlans() as $plan) {
            $plans[$plan->id] = self::planToPayload($plan);
        }

        return $plans;
    }

    /**
     * @return array<int, int>
     */
    public static function optionPlanMap(): array
    {
        $map = [];

        foreach (self::quizOptionsWithPlan() as $option) {
            $planId = (int) ($option->extra['recommended_plan_id'] ?? 0);

            if ($planId > 0) {
                $map[$option->id] = $planId;
            }
        }

        return $map;
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function planForOption(int $optionId): ?array
    {
        $option = LandingBlock::query()
            ->where('block_type', 'option')
            ->where('section_slug', 'quiz')
            ->find($optionId);

        if ($option === null) {
            return null;
        }

        $planId = (int) ($option->extra['recommended_plan_id'] ?? 0);

        if ($planId <= 0) {
            return null;
        }

        $plan = LandingBlock::query()
            ->where('section_slug', 'pricing')
            ->where('block_type', 'plan')
            ->where('is_active', true)
            ->find($planId);

        return $plan !== null ? self::planToPayload($plan) : null;
    }

    /**
     * @return Collection<int, LandingBlock>
     */
    private static function pricingPlans(): Collection
    {
        return LandingBlock::query()
            ->where('section_slug', 'pricing')
            ->where('block_type', 'plan')
            ->where('is_active', true)
            ->with(['children' => fn ($query) => $query
                ->where('block_type', 'feature')
                ->where('is_active', true)
                ->orderBy('sort_order')])
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * @return Collection<int, LandingBlock>
     */
    private static function quizOptionsWithPlan(): Collection
    {
        return LandingBlock::query()
            ->where('section_slug', 'quiz')
            ->where('block_type', 'option')
            ->where('is_active', true)
            ->whereNotNull('extra')
            ->get()
            ->filter(fn (LandingBlock $option): bool => filled($option->extra['recommended_plan_id'] ?? null));
    }

    /**
     * @return array<string, mixed>
     */
    private static function planToPayload(LandingBlock $plan): array
    {
        return [
            'id' => $plan->id,
            'title' => $plan->title,
            'subtitle' => $plan->subtitle,
            'price' => $plan->price,
            'tag' => $plan->tag,
            'isHighlighted' => (bool) $plan->is_highlighted,
            'features' => $plan->children
                ->where('block_type', 'feature')
                ->pluck('title')
                ->filter()
                ->values()
                ->all(),
        ];
    }
}
