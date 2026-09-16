<?php

namespace App\Http\Controllers;

use App\Models\ReferralPlacement;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class ReferralPlacementExportController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $month = $request->date('month') ?? now()->subMonthNoOverflow()->startOfMonth();
        $from = $month->copy()->startOfMonth();
        $to = $month->copy()->endOfMonth();
        $placements = ReferralPlacement::query()->with('participant')
            ->where(fn ($query) => $query->whereNull('starts_at')->orWhere('starts_at', '<=', $to))
            ->where(fn ($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>=', $from))
            ->orderBy('participant_id')->get();

        $stream = fopen('php://temp', 'r+');
        fputcsv($stream, ['Месяц', 'Участник', 'ИНН', 'Площадка', 'URL площадки', 'Реферальная ссылка', 'Материал', 'ERID', 'ОРД', 'Договор', 'Акт', 'Стоимость, коп.', 'Начало', 'Окончание', 'Статус'], ';', '"', '');
        foreach ($placements as $placement) {
            fputcsv($stream, [
                $from->format('m.Y'), $placement->participant->company_name, $placement->participant->inn,
                $placement->platform, $placement->placement_url, url('/r/'.$placement->code), $placement->creative_text,
                $placement->erid ?? '', $placement->ord_name ?? '', $placement->contract_number ?? '', $placement->act_number ?? '',
                $placement->placement_cost_minor ?? '', $placement->starts_at?->format('d.m.Y') ?? '', $placement->ends_at?->format('d.m.Y') ?? '', $placement->status,
            ], ';', '"', '');
        }
        rewind($stream);
        $content = stream_get_contents($stream);
        fclose($stream);

        return response("\xEF\xBB\xBF".$content, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="referral-placements-'.$from->format('Y-m').'.csv"',
        ]);
    }
}
