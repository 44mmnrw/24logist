<?php

namespace App\Services\Community;

use App\Models\CommunityAiScenario;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class CommunityAiScenarioCleaner
{
    public function clear(CommunityAiScenario $scenario): void
    {
        DB::transaction(function () use ($scenario): void {
            $lockedScenario = CommunityAiScenario::query()
                ->lockForUpdate()
                ->findOrFail($scenario->getKey());

            if (in_array($lockedScenario->status, [
                CommunityAiScenario::STATUS_QUEUED,
                CommunityAiScenario::STATUS_PREPARING,
                CommunityAiScenario::STATUS_RUNNING,
            ], true)) {
                throw new RuntimeException('Сначала дождитесь завершения текущей операции или приостановите сценарий.');
            }

            $hasPublishedContent = $lockedScenario->steps()
                ->where(function ($query): void {
                    $query
                        ->whereNotNull('published_at')
                        ->orWhereNotNull('community_post_id')
                        ->orWhereNotNull('community_comment_id');
                })
                ->exists();

            if ($hasPublishedContent) {
                throw new RuntimeException('Нельзя очистить сценарий, который уже начал публиковаться. Удаление сценария не удалит материалы из ленты.');
            }

            $lockedScenario->generations()->delete();
            $lockedScenario->steps()->delete();

            $settings = $lockedScenario->settings ?? [];
            unset(
                $settings['editor_persona_id'],
                $settings['source_message_count'],
                $settings['keyword_match_count'],
            );

            $lockedScenario->update([
                'status' => CommunityAiScenario::STATUS_DRAFT,
                'started_at' => null,
                'completed_at' => null,
                'last_error' => null,
                'settings' => $settings,
            ]);
        });

        $scenario->refresh();
    }
}
