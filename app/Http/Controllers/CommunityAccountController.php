<?php

namespace App\Http\Controllers;

use App\Models\CommunityComment;
use App\Models\CommunityCommentVote;
use App\Models\CommunityPost;
use App\Models\CommunityPostVote;
use App\Models\CommunityUser;
use App\Services\Community\CommunityRanking;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CommunityAccountController extends Controller
{
    public function login(): View|RedirectResponse
    {
        if (auth('community')->check()) {
            return redirect()->route('community.index');
        }

        return view('community.auth.login');
    }

    public function logout(Request $request): RedirectResponse
    {
        auth('community')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('community.index');
    }

    public function onboarding(): View|RedirectResponse
    {
        $user = auth('community')->user();

        if ($user->isOnboarded()) {
            return redirect()->route('community.index');
        }

        return view('community.auth.onboarding', compact('user'));
    }

    public function completeOnboarding(Request $request): RedirectResponse
    {
        $user = auth('community')->user();
        $request->merge(['username' => mb_strtolower(trim((string) $request->input('username')))]);
        $data = $request->validate([
            'username' => [
                'required', 'string', 'min:3', 'max:30', 'regex:/^[\pL\pN_-]+$/u',
                Rule::unique('community_users', 'username')->ignore($user->id),
            ],
            'accept_terms' => ['accepted'],
            'telegram_notifications' => ['nullable', 'boolean'],
            'max_notifications' => ['nullable', 'boolean'],
        ], [
            'username.regex' => 'Используйте буквы, цифры, дефис или подчёркивание.',
            'accept_terms.accepted' => 'Необходимо принять правила сообщества и политику конфиденциальности.',
        ]);

        $user->update([
            'username' => $data['username'],
            'onboarded_at' => now(),
            'terms_accepted_at' => now(),
        ]);

        foreach (['telegram', 'max'] as $provider) {
            $enabled = (bool) ($data[$provider.'_notifications'] ?? false);
            $user->identities()->where('provider', $provider)->where('bot_access', true)->update([
                'notifications_enabled' => $enabled,
            ]);
        }

        return redirect()->intended(route('community.index'))->with('status', 'Профиль сообщества создан.');
    }

    public function profile(CommunityUser $user): View
    {
        abort_if($user->trashed() || ! $user->isOnboarded(), 404);
        $posts = $user->posts()->published()->with('category')->latest('published_at')->paginate(15);

        return view('community.profile', compact('user', 'posts'));
    }

    public function settings(): View
    {
        $user = auth('community')->user()->load('identities');

        return view('community.auth.settings', compact('user'));
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $user = auth('community')->user();
        $data = $request->validate([
            'telegram_notifications' => ['nullable', 'boolean'],
            'max_notifications' => ['nullable', 'boolean'],
        ]);

        foreach (['telegram', 'max'] as $provider) {
            $identity = $user->identities()->where('provider', $provider)->first();

            if ($identity !== null) {
                $identity->update([
                    'notifications_enabled' => $identity->bot_access && (bool) ($data[$provider.'_notifications'] ?? false),
                ]);
            }
        }

        return back()->with('status', 'Настройки уведомлений сохранены.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->validate(['confirmation' => ['required', 'in:УДАЛИТЬ']]);
        /** @var CommunityUser $user */
        $user = auth('community')->user();

        DB::transaction(function () use ($user): void {
            CommunityPostVote::query()->where('community_user_id', $user->id)->get()->each(function (CommunityPostVote $vote): void {
                $post = CommunityPost::query()->find($vote->community_post_id);

                if ($post !== null) {
                    $post->score -= $vote->value;
                    $post->hot_score = CommunityRanking::hotScore($post->score, $post->published_at ?? $post->created_at);
                    $post->save();
                }
            });
            CommunityCommentVote::query()->where('community_user_id', $user->id)->get()->each(function (CommunityCommentVote $vote): void {
                CommunityComment::query()->whereKey($vote->community_comment_id)->decrement('score', $vote->value);
            });
            $user->posts()->update(['community_user_id' => null]);
            $user->comments()->update(['community_user_id' => null]);
            $user->identities()->delete();
            $user->forceDelete();
        });

        auth('community')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('community.index')->with('status', 'Аккаунт удалён, публикации обезличены.');
    }
}
