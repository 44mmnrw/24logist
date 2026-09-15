<?php

namespace App\Services\Community;

use App\Jobs\PublishCommunityAiScenarioStep;
use App\Models\CommunityAiScenario;
use RuntimeException;

final class CommunityAiScenarioPublisher
{
    public function schedule(CommunityAiScenario $scenario): void
    {
        if (! in_array($scenario->status, [CommunityAiScenario::STATUS_APPROVED, CommunityAiScenario::STATUS_PAUSED], true)) {
            throw new RuntimeException('Запустить можно только одобренный или приостановленный сценарий.');
        }

        $steps = $scenario->steps()
            ->whereIn('status', ['approved', 'scheduled'])
            ->orderBy('sequence')
            ->get();

        if ($steps->isEmpty() || $steps->first()->type !== 'topic') {
            throw new RuntimeException('В сценарии нет одобренной темы.');
        }

        $startsAt = $scenario->planned_at?->isFuture() === true ? $scenario->planned_at : now();

        foreach ($steps as $step) {
            $scheduledAt = $startsAt->copy()->addMinutes($step->planned_delay_minutes);
            $step->update(['status' => 'scheduled', 'scheduled_at' => $scheduledAt, 'last_error' => null]);
            PublishCommunityAiScenarioStep::dispatch($step->id)->delay($scheduledAt);
        }

        $scenario->update([
            'status' => CommunityAiScenario::STATUS_SCHEDULED,
            'planned_at' => $startsAt,
            'last_error' => null,
        ]);
    }
}
