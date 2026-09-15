<?php

namespace App\Jobs;

use App\Models\CommunityAiScenario;
use App\Services\Community\CommunityAiScenarioPreparer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class PrepareCommunityAiScenario implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 600;

    /** @var list<int> */
    public array $backoff = [60, 300];

    public function __construct(public int $scenarioId)
    {
        $this->onQueue('community-ai');
    }

    public function handle(CommunityAiScenarioPreparer $preparer): void
    {
        $scenario = CommunityAiScenario::query()->find($this->scenarioId);
        if ($scenario === null || in_array($scenario->status, [CommunityAiScenario::STATUS_CANCELLED, CommunityAiScenario::STATUS_COMPLETED], true)) {
            return;
        }

        $preparer->prepare($scenario);
    }

    public function failed(?Throwable $exception): void
    {
        CommunityAiScenario::query()->whereKey($this->scenarioId)->update([
            'status' => CommunityAiScenario::STATUS_FAILED,
            'last_error' => $exception ? mb_substr($exception->getMessage(), 0, 2000) : 'Подготовка сценария завершилась ошибкой.',
        ]);
    }
}
