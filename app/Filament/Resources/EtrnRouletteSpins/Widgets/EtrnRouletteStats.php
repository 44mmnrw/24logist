<?php

namespace App\Filament\Resources\EtrnRouletteSpins\Widgets;

use App\Models\EtrnRouletteSpin;
use App\Services\EtrnRouletteOutcome;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class EtrnRouletteStats extends StatsOverviewWidget
{
    protected ?string $heading = 'Результаты игры';

    protected ?string $description = 'По сохранённым вращениям. Счётчики на игровом экране сбрасываются отдельно.';

    protected ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        $counts = EtrnRouletteSpin::query()
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN result_kind = 'jackpot' THEN 1 ELSE 0 END) as jackpots")
            ->selectRaw("SUM(CASE WHEN result_kind = 'match' THEN 1 ELSE 0 END) as matches")
            ->selectRaw("SUM(CASE WHEN result_kind = 'miss' THEN 1 ELSE 0 END) as misses")
            ->selectRaw('SUM(CASE WHEN etrn_roulette_player_id IS NOT NULL THEN 1 ELSE 0 END) as prize_spins')
            ->selectRaw('SUM(CASE WHEN community_user_id IS NULL THEN 1 ELSE 0 END) as guest_spins')
            ->first();

        $screenCounters = DB::table('game_counters')
            ->whereIn('key', ['etrn-roulette', 'etrn-roulette-superbonus', 'etrn-roulette-jackpot-streak'])
            ->pluck('attempts', 'key');

        $streak = (int) ($screenCounters['etrn-roulette-jackpot-streak'] ?? $screenCounters['etrn-roulette'] ?? 0);
        $chance = app(EtrnRouletteOutcome::class)->nextJackpotChancePercent($streak);

        return [
            Stat::make('На экране: попытки', number_format((int) ($screenCounters['etrn-roulette'] ?? 0), 0, ',', ' ')),
            Stat::make('На экране: супербонусы', number_format((int) ($screenCounters['etrn-roulette-superbonus'] ?? 0), 0, ',', ' ')),
            Stat::make('Шанс следующего супербонуса', number_format($chance, 3, ',', ' ').' %'),
            Stat::make('Вращений в журнале', number_format((int) $counts->total, 0, ',', ' ')),
            Stat::make('Совпадений', number_format((int) $counts->matches, 0, ',', ' ')),
            Stat::make('3 логистРу', number_format((int) $counts->jackpots, 0, ',', ' ')),
            Stat::make('Без совпадения', number_format((int) $counts->misses, 0, ',', ' ')),
            Stat::make('Вращений за приз', number_format((int) $counts->prize_spins, 0, ',', ' ')),
            Stat::make('Вращений гостей', number_format((int) $counts->guest_spins, 0, ',', ' ')),
        ];
    }
}
