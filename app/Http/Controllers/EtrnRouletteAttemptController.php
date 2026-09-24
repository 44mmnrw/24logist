<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class EtrnRouletteAttemptController extends Controller
{
    private const COUNTER_KEY = 'etrn-roulette';
    private const JACKPOT_COUNTER_KEY = 'etrn-roulette-superbonus';

    public function index(): JsonResponse
    {
        return $this->response($this->currentAttempts(), $this->currentJackpots());
    }

    public function store(Request $request): JsonResponse
    {
        $communityUserId = auth('community')->id();
        $isJackpot = $request->boolean('jackpot');

        [$attempts, $jackpots, $playerAttempts] = DB::transaction(function () use ($communityUserId, $isJackpot): array {
            $counter = DB::table('game_counters')
                ->where('key', self::COUNTER_KEY)
                ->lockForUpdate()
                ->first();

            if ($counter === null) {
                DB::table('game_counters')->insert([
                    'key' => self::COUNTER_KEY,
                    'attempts' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $attempts = 1;
            } else {
                $attempts = (int) $counter->attempts + 1;

                DB::table('game_counters')
                    ->where('key', self::COUNTER_KEY)
                    ->update([
                        'attempts' => $attempts,
                        'updated_at' => now(),
                    ]);
            }

            $jackpotCounter = DB::table('game_counters')
                ->where('key', self::JACKPOT_COUNTER_KEY)
                ->lockForUpdate()
                ->first();
            $jackpots = (int) ($jackpotCounter->attempts ?? 0);

            if ($isJackpot) {
                $jackpots++;

                if ($jackpotCounter === null) {
                    DB::table('game_counters')->insert([
                        'key' => self::JACKPOT_COUNTER_KEY,
                        'attempts' => $jackpots,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                } else {
                    DB::table('game_counters')
                        ->where('key', self::JACKPOT_COUNTER_KEY)
                        ->update([
                            'attempts' => $jackpots,
                            'updated_at' => now(),
                        ]);
                }
            }

            $playerAttempts = null;
            if ($communityUserId !== null) {
                $player = DB::table('etrn_roulette_players')
                    ->where('community_user_id', $communityUserId)
                    ->lockForUpdate()
                    ->first();

                if ($player !== null) {
                    $playerAttempts = (int) $player->attempts + 1;
                    DB::table('etrn_roulette_players')
                        ->where('id', $player->id)
                        ->update([
                            'attempts' => $playerAttempts,
                            'last_played_at' => now(),
                            'updated_at' => now(),
                        ]);
                }
            }

            return [$attempts, $jackpots, $playerAttempts];
        });

        return $this->response($attempts, $jackpots, $playerAttempts);
    }

    private function currentAttempts(): int
    {
        return (int) (DB::table('game_counters')
            ->where('key', self::COUNTER_KEY)
            ->value('attempts') ?? 0);
    }

    private function currentJackpots(): int
    {
        return (int) (DB::table('game_counters')
            ->where('key', self::JACKPOT_COUNTER_KEY)
            ->value('attempts') ?? 0);
    }

    private function response(int $attempts, int $jackpots, ?int $playerAttempts = null): JsonResponse
    {
        $payload = ['attempts' => $attempts, 'jackpots' => $jackpots];
        if ($playerAttempts !== null) {
            $payload['player_attempts'] = $playerAttempts;
        }

        return response()
            ->json($payload)
            ->header('Cache-Control', 'no-store, private');
    }
}
