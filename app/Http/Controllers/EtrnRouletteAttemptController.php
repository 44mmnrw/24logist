<?php

namespace App\Http\Controllers;

use App\Models\EtrnRouletteSpin;
use App\Services\EtrnRouletteOutcome;
use App\Services\SmartCaptchaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class EtrnRouletteAttemptController extends Controller
{
    private const COUNTER_KEY = 'etrn-roulette';
    private const JACKPOT_COUNTER_KEY = 'etrn-roulette-superbonus';

    private const JACKPOT_STREAK_KEY = 'etrn-roulette-jackpot-streak';

    public function index(): JsonResponse
    {
        return $this->response($this->currentAttempts(), $this->currentJackpots());
    }

    public function store(Request $request, EtrnRouletteOutcome $outcomeGenerator, SmartCaptchaService $captcha): JsonResponse
    {
        $validated = $request->validate(['request_id' => ['required', 'uuid']]);
        $requestId = $validated['request_id'];
        $communityUserId = auth('community')->id();
        $actorKey = hash('sha256', $communityUserId === null
            ? 'session:'.$request->session()->getId()
            : 'user:'.$communityUserId);

        $existing = EtrnRouletteSpin::query()->where('request_id', $requestId)->first();
        if ($existing !== null) {
            abort_if($existing->actor_key !== $actorKey, 409);
            return $this->spinResponse($existing, true);
        }

        $validated = $request->validate(['smart_token' => ['required', 'string', 'max:4096']]);
        $token = trim($validated['smart_token']);
        if ($token === '') {
            throw ValidationException::withMessages(['smart_token' => 'Подтвердите, что вы не робот.']);
        }
        $captcha->validate('etrn_roulette', $token, $request->ip(), $request->getHost());

        $ipAddress = $request->ip();
        $userAgent = $request->userAgent();

        $result = DB::transaction(function () use ($requestId, $communityUserId, $actorKey, $outcomeGenerator, $ipAddress, $userAgent): array {
            // Serialize spin creation, including guest requests and first-time jackpots.
            $counter = DB::table('game_counters')
                ->where('key', self::COUNTER_KEY)
                ->lockForUpdate()
                ->first();
            $jackpotStreak = DB::table('game_counters')
                ->where('key', self::JACKPOT_STREAK_KEY)
                ->lockForUpdate()
                ->first();

            $existing = EtrnRouletteSpin::query()->where('request_id', $requestId)->first();
            if ($existing !== null) {
                abort_if($existing->actor_key !== $actorKey, 409);
                return ['spin' => $existing, 'replayed' => true];
            }

            $player = $communityUserId === null ? null : DB::table('etrn_roulette_players')
                ->where('community_user_id', $communityUserId)
                ->whereNotNull('contact_verified_at')
                ->lockForUpdate()
                ->first();

            $attemptsWithoutJackpot = (int) ($jackpotStreak?->attempts ?? $counter?->attempts ?? 0);
            $outcome = $outcomeGenerator->generate(null, $attemptsWithoutJackpot);
            $outcome['jackpot_chance_percent'] = $outcomeGenerator->nextJackpotChancePercent($attemptsWithoutJackpot);
            $spin = EtrnRouletteSpin::query()->create([
                'request_id' => $requestId,
                'actor_key' => $actorKey,
                'community_user_id' => $communityUserId,
                'etrn_roulette_player_id' => $player?->id,
                'is_jackpot' => $outcome['jackpot'],
                'outcome' => $outcome,
                'result_kind' => $outcome['jackpot'] ? 'jackpot' : ($outcome['matched'] ? 'match' : 'miss'),
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent === null ? null : mb_substr($userAgent, 0, 512),
            ]);

            if ($counter === null) {
                DB::table('game_counters')->insert([
                    'key' => self::COUNTER_KEY,
                    'attempts' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                DB::table('game_counters')->where('key', self::COUNTER_KEY)->update([
                    'attempts' => (int) $counter->attempts + 1,
                    'updated_at' => now(),
                ]);
            }

            if ($outcome['jackpot']) {
                $jackpotCounter = DB::table('game_counters')
                    ->where('key', self::JACKPOT_COUNTER_KEY)
                    ->first();
                if ($jackpotCounter === null) {
                    DB::table('game_counters')->insert([
                        'key' => self::JACKPOT_COUNTER_KEY,
                        'attempts' => 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                } else {
                    DB::table('game_counters')->where('key', self::JACKPOT_COUNTER_KEY)->update([
                        'attempts' => (int) $jackpotCounter->attempts + 1,
                        'updated_at' => now(),
                    ]);
                }
            }

            $nextStreak = $outcome['jackpot'] ? 0 : $attemptsWithoutJackpot + 1;
            if ($jackpotStreak === null) {
                DB::table('game_counters')->insert([
                    'key' => self::JACKPOT_STREAK_KEY,
                    'attempts' => $nextStreak,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                DB::table('game_counters')->where('key', self::JACKPOT_STREAK_KEY)->update([
                    'attempts' => $nextStreak,
                    'updated_at' => now(),
                ]);
            }

            if ($player !== null) {
                DB::table('etrn_roulette_players')->where('id', $player->id)->update([
                    'attempts' => (int) $player->attempts + 1,
                    'last_played_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            return ['spin' => $spin, 'replayed' => false];
        });

        return $this->spinResponse($result['spin'], $result['replayed']);
    }

    private function spinResponse(EtrnRouletteSpin $spin, bool $replayed): JsonResponse
    {
        $playerAttempts = $spin->etrn_roulette_player_id === null ? null :
            (int) DB::table('etrn_roulette_players')->where('id', $spin->etrn_roulette_player_id)->value('attempts');

        return $this->response($this->currentAttempts(), $this->currentJackpots(), $playerAttempts, [
            'outcome' => $spin->outcome,
            'spin_id' => $spin->id,
            'replayed' => $replayed,
        ]);
    }

    private function currentAttempts(): int
    {
        return (int) (DB::table('game_counters')->where('key', self::COUNTER_KEY)->value('attempts') ?? 0);
    }

    private function currentJackpots(): int
    {
        return (int) (DB::table('game_counters')->where('key', self::JACKPOT_COUNTER_KEY)->value('attempts') ?? 0);
    }

    private function currentJackpotStreak(): int
    {
        return (int) (DB::table('game_counters')->where('key', self::JACKPOT_STREAK_KEY)->value('attempts')
            ?? $this->currentAttempts());
    }

    private function response(int $attempts, int $jackpots, ?int $playerAttempts = null, array $extra = []): JsonResponse
    {
        $payload = [
            'attempts' => $attempts,
            'jackpots' => $jackpots,
            'next_jackpot_chance_percent' => app(EtrnRouletteOutcome::class)
                ->nextJackpotChancePercent($this->currentJackpotStreak()),
            ...$extra,
        ];
        if ($playerAttempts !== null) {
            $payload['player_attempts'] = $playerAttempts;
        }

        return response()->json($payload)->header('Cache-Control', 'no-store, private');
    }
}
