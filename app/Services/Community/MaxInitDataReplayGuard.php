<?php

namespace App\Services\Community;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class MaxInitDataReplayGuard
{
    /** @param array{query_id?: mixed} $maxData */
    public function consume(array $maxData): void
    {
        $queryId = $maxData['query_id'] ?? null;

        if (! is_string($queryId) || $queryId === '' || strlen($queryId) > 512) {
            throw ValidationException::withMessages([
                'max' => 'MAX не передал идентификатор сессии. Откройте мини-приложение заново.',
            ]);
        }

        DB::table('community_max_init_data_uses')
            ->where('expires_at', '<', now())
            ->delete();

        $inserted = DB::table('community_max_init_data_uses')->insertOrIgnore([
            'query_id_hash' => hash('sha256', $queryId),
            'expires_at' => now()->addSeconds((int) config('community.max.init_data_ttl', 3600)),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($inserted !== 1) {
            throw ValidationException::withMessages([
                'max' => 'Эти данные MAX уже использованы. Откройте мини-приложение заново.',
            ]);
        }
    }
}
