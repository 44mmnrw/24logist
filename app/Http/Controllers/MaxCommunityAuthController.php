<?php

namespace App\Http\Controllers;

use App\Models\CommunityLoginChallenge;
use App\Models\CommunityUser;
use App\Services\Community\CommunityIdentityManager;
use App\Services\Community\MaxInitDataValidator;
use App\Services\SiteSettingsService;
use chillerlan\QRCode\QRCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class MaxCommunityAuthController extends Controller
{
    public function __construct(private readonly SiteSettingsService $siteSettings) {}

    public function start(Request $request): View
    {
        abort_unless($this->siteSettings->communityMaxEnabled(), 404);
        abort_if(blank($this->siteSettings->maxBotUsername()) || blank($this->siteSettings->maxBotToken()), 503, 'MAX-вход ещё не настроен.');
        $token = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
        $browserBinding = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
        $challenge = CommunityLoginChallenge::query()->create([
            'token_hash' => hash('sha256', $token),
            'browser_session_hash' => hash('sha256', $browserBinding),
            'link_to_user_id' => auth('community')->id(),
            'expires_at' => now()->addSeconds((int) config('community.max.challenge_ttl', 300)),
        ]);
        $request->session()->put('community.max_challenges.'.$challenge->id, $browserBinding);
        $deepLink = 'https://max.ru/'.ltrim($this->siteSettings->maxBotUsername(), '@').'?startapp='.rawurlencode($token);
        $qr = (new QRCode)->render($deepLink);

        return view('community.auth.max', compact('challenge', 'deepLink', 'qr'));
    }

    public function approve(
        Request $request,
        MaxInitDataValidator $validator,
        CommunityIdentityManager $identities,
    ): JsonResponse {
        abort_unless($this->siteSettings->communityMaxEnabled(), 404);
        $data = $request->validate(['challenge' => ['required', 'string', 'max:128'], 'init_data' => ['required', 'string', 'max:10000']]);
        $maxData = $validator->validate($data['init_data']);

        $challenge = DB::transaction(function () use ($data, $maxData, $identities): CommunityLoginChallenge {
            $challenge = CommunityLoginChallenge::query()
                ->where('token_hash', hash('sha256', $data['challenge']))
                ->lockForUpdate()
                ->first();

            if ($challenge === null || $challenge->status !== 'pending' || $challenge->expires_at->isPast()) {
                throw ValidationException::withMessages(['challenge' => 'Ссылка входа истекла или уже использована.']);
            }

            $linkTo = $challenge->link_to_user_id
                ? CommunityUser::query()->find($challenge->link_to_user_id)
                : null;
            $user = $identities->resolve('max', (string) $maxData['user']['id'], $linkTo, true);
            $user->identities()->where('provider', 'max')->update(['bot_status' => 'active']);
            $challenge->update([
                'community_user_id' => $user->id,
                'status' => 'approved',
                'approved_at' => now(),
            ]);

            return $challenge;
        });

        return response()->json(['ok' => true, 'challenge_id' => $challenge->id]);
    }

    public function session(
        Request $request,
        MaxInitDataValidator $validator,
        CommunityIdentityManager $identities,
    ): JsonResponse {
        abort_unless($this->siteSettings->communityMaxEnabled(), 404);
        $data = $request->validate(['init_data' => ['required', 'string', 'max:10000']]);
        $maxData = $validator->validate($data['init_data']);
        $linkTo = $request->boolean('link') ? auth('community')->user() : null;
        $user = $identities->resolve('max', (string) $maxData['user']['id'], $linkTo, true);
        $user->identities()->where('provider', 'max')->update(['bot_status' => 'active']);
        auth('community')->login($user, true);
        $request->session()->regenerate();

        return response()->json([
            'ok' => true,
            'redirect' => $user->isOnboarded()
                ? $request->session()->pull('url.intended', route('community.index'))
                : route('community.onboarding'),
        ]);
    }

    public function status(Request $request, CommunityLoginChallenge $challenge): JsonResponse
    {
        abort_unless($this->siteSettings->communityMaxEnabled(), 404);

        $browserBinding = (string) $request->session()->get('community.max_challenges.'.$challenge->id);
        abort_if($browserBinding === '', 403);
        abort_unless(hash_equals($challenge->browser_session_hash, hash('sha256', $browserBinding)), 403);

        if ($challenge->expires_at->isPast() && $challenge->status === 'pending') {
            $challenge->update(['status' => 'expired']);
        }

        if ($challenge->status !== 'approved' || $challenge->consumed_at !== null) {
            return response()->json(['status' => $challenge->status]);
        }

        $challenge = DB::transaction(function () use ($challenge): CommunityLoginChallenge {
            $locked = CommunityLoginChallenge::query()->lockForUpdate()->findOrFail($challenge->id);

            if ($locked->status !== 'approved' || $locked->consumed_at !== null) {
                throw ValidationException::withMessages(['challenge' => 'Вход уже завершён.']);
            }

            $locked->update(['status' => 'consumed', 'consumed_at' => now()]);

            return $locked;
        });
        $user = CommunityUser::query()->findOrFail($challenge->community_user_id);
        auth('community')->login($user, true);
        $request->session()->forget('community.max_challenges.'.$challenge->id);
        $request->session()->regenerate();

        return response()->json([
            'status' => 'consumed',
            'redirect' => $user->isOnboarded()
                ? $request->session()->pull('url.intended', route('community.index'))
                : route('community.onboarding'),
        ]);
    }
}
