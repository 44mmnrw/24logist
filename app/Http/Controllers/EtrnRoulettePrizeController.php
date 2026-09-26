<?php

namespace App\Http\Controllers;

use App\Mail\EtrnRouletteContactCode;
use App\Models\EtrnRoulettePlayer;
use App\Services\SiteMailService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

final class EtrnRoulettePrizeController extends Controller
{
    private const CONTACT_SESSION_KEY = 'etrn_roulette_prize_contact';

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

    public function __invoke(Request $request): View|RedirectResponse
    {
        if (auth('community')->user()->etrnRoulettePlayer()->whereNotNull('contact_verified_at')->exists()) {
            return redirect()->route('etrn-roulette');
        }

        $pending = $request->session()->get(self::CONTACT_SESSION_KEY);

        return view('etrn-roulette-prize-contact', [
            'pendingEmail' => is_array($pending)
                && ($pending['user_id'] ?? null) === auth('community')->id()
                && ($pending['expires_at'] ?? 0) > now()->timestamp
                    ? $pending['email']
                    : null,
        ]);
    }

    public function sendContactCode(Request $request, SiteMailService $siteMail): RedirectResponse
    {
        $request->merge(['email' => mb_strtolower(trim((string) $request->input('email')))]);
        $data = $request->validate([
            'email' => ['required', 'email:rfc', 'max:255'],
            'contact_consent' => ['accepted'],
        ], [
            'contact_consent.accepted' => 'Подтвердите согласие на обработку email для участия в розыгрыше.',
        ]);

        $userId = auth('community')->id();
        $pending = $request->session()->get(self::CONTACT_SESSION_KEY);
        if (is_array($pending)
            && ($pending['user_id'] ?? null) === $userId
            && ($pending['sent_at'] ?? 0) > now()->subMinute()->timestamp) {
            throw ValidationException::withMessages(['email' => 'Повторно запросить код можно через минуту.']);
        }

        $userLimit = 'etrn-prize-email-user:'.$userId;
        $emailLimit = 'etrn-prize-email-address:'.hash('sha256', $data['email']);
        if (RateLimiter::tooManyAttempts($userLimit, 5) || RateLimiter::tooManyAttempts($emailLimit, 10)) {
            throw ValidationException::withMessages(['email' => 'Слишком много запросов кода. Повторите позже.']);
        }
        $code = sprintf('%06d', random_int(0, 999999));
        try {
            $siteMail->apply();
            Mail::to($data['email'])->send(new EtrnRouletteContactCode($code));
        } catch (Throwable $exception) {
            report($exception);
            throw ValidationException::withMessages(['email' => 'Не удалось отправить код. Попробуйте позже.']);
        }
        RateLimiter::hit($userLimit, 3600);
        RateLimiter::hit($emailLimit, 3600);

        $request->session()->put(self::CONTACT_SESSION_KEY, [
            'user_id' => $userId,
            'email' => $data['email'],
            'code_hash' => Hash::make($code),
            'sent_at' => now()->timestamp,
            'expires_at' => now()->addMinutes(10)->timestamp,
            'attempts' => 0,
            'consent_at' => now()->toIso8601String(),
            'consent_ip' => $request->ip(),
        ]);

        return redirect()->route('etrn-roulette.prize.join')->with('status', 'Код отправлен на указанный email.');
    }

    public function verifyContactCode(Request $request): RedirectResponse
    {
        $data = $request->validate(['code' => ['required', 'regex:/^\d{6}$/']]);
        $pending = $request->session()->get(self::CONTACT_SESSION_KEY);
        if (! is_array($pending)
            || ($pending['user_id'] ?? null) !== auth('community')->id()
            || ($pending['expires_at'] ?? 0) <= now()->timestamp
            || ($pending['attempts'] ?? 0) >= 5) {
            $request->session()->forget(self::CONTACT_SESSION_KEY);
            throw ValidationException::withMessages(['code' => 'Код истёк. Запросите новый.']);
        }

        if (! Hash::check($data['code'], $pending['code_hash'])) {
            $pending['attempts']++;
            $request->session()->put(self::CONTACT_SESSION_KEY, $pending);
            throw ValidationException::withMessages(['code' => 'Неверный код подтверждения.']);
        }

        $player = EtrnRoulettePlayer::query()->firstOrCreate(
            ['community_user_id' => auth('community')->id()],
            ['attempts' => 0, 'joined_at' => now()],
        );
        $player->update([
            'contact_email' => $pending['email'],
            'contact_verified_at' => now(),
            'contact_consent_at' => $pending['consent_at'],
            'contact_consent_ip' => $pending['consent_ip'],
        ]);
        $request->session()->forget(self::CONTACT_SESSION_KEY);

        return redirect()
            ->route('etrn-roulette')
            ->with('status', 'Email подтверждён. Следующие попытки учитываются в розыгрыше подписки.');
    }
}
