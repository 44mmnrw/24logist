<?php

namespace App\Http\Controllers;

use App\Models\ReferralProgramSetting;
use App\Services\Referral\ReferralProgramService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Cookie;

final class ReferralRedirectController extends Controller
{
    public function __invoke(Request $request, string $code, ReferralProgramService $program): RedirectResponse
    {
        $cookieName = (string) config('referrals.cookie_name', 'logistru_referral');
        if ($request->cookie($cookieName) !== null) {
            return redirect('/');
        }

        if (! $program->recordClick($code, $request->ip(), $request->userAgent())) {
            return redirect('/');
        }

        $minutes = ReferralProgramSetting::current()->attribution_days * 24 * 60;
        $cookie = cookie($cookieName, $code, $minutes, '/', null, true, true, false, Cookie::SAMESITE_LAX);

        return redirect('/')->withCookie($cookie);
    }
}
