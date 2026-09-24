<?php

namespace App\Http\Controllers;

use App\Models\EtrnRoulettePlayer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class EtrnRoulettePrizeController extends Controller
{
    public function auth(Request $request, string $provider): RedirectResponse
    {
        if (auth('community')->check()) {
            return redirect()->route('etrn-roulette.prize.join');
        }

        $providerRoutes = [
            'telegram' => 'community.auth.telegram.redirect',
            'vk' => 'community.auth.vk.redirect',
            'max' => 'community.auth.max.start',
        ];

        abort_unless(isset($providerRoutes[$provider]), 404);
        $request->session()->put('url.intended', route('etrn-roulette.prize.join'));

        return redirect()->route($providerRoutes[$provider]);
    }

    public function __invoke(): RedirectResponse
    {
        EtrnRoulettePlayer::query()->firstOrCreate(
            ['community_user_id' => auth('community')->id()],
            ['attempts' => 0, 'joined_at' => now()],
        );

        return redirect()
            ->route('etrn-roulette')
            ->with('status', 'Вы участвуете в розыгрыше подписки. Каждая новая попытка учитывается.');
    }
}
