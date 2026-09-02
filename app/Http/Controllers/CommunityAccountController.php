<?php

namespace App\Http\Controllers;

use App\Models\CommunityComment;
use App\Models\CommunityCommentVote;
use App\Models\CommunityPost;
use App\Models\CommunityPostVote;
use App\Models\CommunityUser;
use App\Services\Community\CommunityRanking;
use App\Services\Community\CommunityAvatarService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
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
            'display_name' => $data['username'],
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

    public function updateSettings(Request $request, CommunityAvatarService $avatars): RedirectResponse
    {
        $user = auth('community')->user();
        $request->merge([
            'display_name' => preg_replace('/\s+/u', ' ', trim((string) $request->input('display_name', $user->displayName()))),
            'bio' => trim((string) $request->input('bio', $user->bio)),
        ]);
        $data = $request->validate([
            'display_name' => ['required', 'string', 'min:2', 'max:50', 'regex:/^[\pL\pN][\pL\pN ._-]*$/u'],
            'transport_role' => ['nullable', Rule::in(array_keys(CommunityUser::TRANSPORT_ROLES))],
            'bio' => ['nullable', 'string', 'max:1000'],
            'telegram_notifications' => ['nullable', 'boolean'],
            'max_notifications' => ['nullable', 'boolean'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072', 'dimensions:max_width=4096,max_height=4096'],
            'remove_avatar' => ['nullable', 'boolean'],
        ], [
            'display_name.regex' => 'Никнейм может содержать буквы, цифры, пробелы, точку, дефис и подчёркивание.',
        ]);

        if ((bool) ($data['remove_avatar'] ?? false)) {
            $avatars->remove($user);
        } elseif ($request->hasFile('avatar') && ! $avatars->storeUpload($user, $request->file('avatar'))) {
            throw ValidationException::withMessages(['avatar' => 'Не удалось обработать изображение. Выберите другой файл.']);
        }

        $user->update([
            'display_name' => $data['display_name'],
            'transport_role' => array_key_exists('transport_role', $data)
                ? (filled($data['transport_role']) ? $data['transport_role'] : null)
                : $user->transport_role,
            'bio' => filled($data['bio'] ?? null) ? $data['bio'] : null,
        ]);

        foreach (['telegram', 'max'] as $provider) {
            $identity = $user->identities()->where('provider', $provider)->first();

            if ($identity !== null) {
                $identity->update([
                    'notifications_enabled' => $identity->bot_access && (bool) ($data[$provider.'_notifications'] ?? false),
                ]);
            }
        }

        return back()->with('status', 'Настройки профиля сохранены.');
    }

    public function destroy(Request $request, CommunityAvatarService $avatars): RedirectResponse
    {
        $request->validate(['confirmation' => ['required', 'in:УДАЛИТЬ']]);
        /** @var CommunityUser $user */
        $user = auth('community')->user();
        $avatarPath = $user->avatar_path;

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
        $avatars->deletePath($avatarPath);

        auth('community')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('community.index')->with('status', 'Аккаунт удалён, публикации обезличены.');
    }
}
