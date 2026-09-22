<?php

namespace App\Http\Controllers;

use App\Models\CommunityComment;
use App\Models\CommunityCommentVote;
use App\Models\CommunityPost;
use App\Models\CommunityPostVote;
use App\Models\CommunityUser;
use App\Services\Community\CommunityAvatarService;
use App\Services\Community\CommunityKarmaService;
use App\Services\Community\CommunityRanking;
use App\Services\Community\CommunitySessionTracker;
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

    public function logout(Request $request, CommunitySessionTracker $sessions): RedirectResponse
    {
        /** @var CommunityUser $user */
        $user = auth('community')->user();
        $sessions->logout($request, $user);
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
            'transport_role' => ['required', Rule::in(array_keys(CommunityUser::TRANSPORT_ROLES))],
            'accept_terms' => ['accepted'],
            'telegram_notifications' => ['nullable', 'boolean'],
            'max_notifications' => ['nullable', 'boolean'],
        ], [
            'username.regex' => 'Используйте буквы, цифры, дефис или подчёркивание.',
            'transport_role.required' => 'Выберите роль в перевозках.',
            'transport_role.in' => 'Выберите роль в перевозках из списка.',
            'accept_terms.accepted' => 'Необходимо принять правила сообщества и политику конфиденциальности.',
        ]);

        $user->update([
            'username' => $data['username'],
            'display_name' => $data['username'],
            'transport_role' => $data['transport_role'],
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
        $posts = $user->posts()->published()->with(['category', 'photos'])->latest('published_at')->paginate(15);

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
            'display_name' => preg_replace('/\s+/u', ' ', trim((string) $request->input('display_name', $user->display_name ?: $user->username))),
            'first_name' => preg_replace('/\s+/u', ' ', trim((string) $request->input('first_name', $user->first_name))),
            'last_name' => preg_replace('/\s+/u', ' ', trim((string) $request->input('last_name', $user->last_name))),
            'bio' => trim((string) $request->input('bio', $user->bio)),
        ]);
        $data = $request->validate([
            'display_name' => ['required', 'string', 'min:2', 'max:50', 'regex:/^[\pL\pN][\pL\pN ._-]*$/u'],
            'first_name' => ['nullable', 'string', 'max:50', "regex:/^[\pL][\pL '\u{2019}-]*$/u"],
            'last_name' => ['nullable', 'string', 'max:50', "regex:/^[\pL][\pL '\u{2019}-]*$/u"],
            'show_first_name' => ['nullable', 'boolean'],
            'show_last_name' => ['nullable', 'boolean'],
            'transport_role' => ['nullable', Rule::in(array_keys(CommunityUser::TRANSPORT_ROLES))],
            'bio' => ['nullable', 'string', 'max:1000'],
            'show_karma' => ['nullable', 'boolean'],
            'telegram_notifications' => ['nullable', 'boolean'],
            'max_notifications' => ['nullable', 'boolean'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192', 'dimensions:max_width=4096,max_height=4096'],
            'remove_avatar' => ['nullable', 'boolean'],
        ], [
            'display_name.regex' => 'Никнейм может содержать буквы, цифры, пробелы, точку, дефис и подчёркивание.',
            'first_name.regex' => 'Имя может содержать буквы, пробел, дефис или апостроф.',
            'last_name.regex' => 'Фамилия может содержать буквы, пробел, дефис или апостроф.',
        ]);

        if ($request->hasFile('avatar') && ! $avatars->storeUpload($user, $request->file('avatar'))) {
            throw ValidationException::withMessages(['avatar' => 'Не удалось обработать изображение. Выберите другой файл.']);
        } elseif (! $request->hasFile('avatar') && (bool) ($data['remove_avatar'] ?? false)) {
            $avatars->remove($user);
        }

        $user->update([
            'display_name' => $data['display_name'],
            'first_name' => filled($data['first_name'] ?? null) ? $data['first_name'] : null,
            'last_name' => filled($data['last_name'] ?? null) ? $data['last_name'] : null,
            'show_first_name' => filled($data['first_name'] ?? null) && (bool) ($data['show_first_name'] ?? false),
            'show_last_name' => filled($data['last_name'] ?? null) && (bool) ($data['show_last_name'] ?? false),
            'transport_role' => array_key_exists('transport_role', $data)
                ? (filled($data['transport_role']) ? $data['transport_role'] : null)
                : $user->transport_role,
            'bio' => filled($data['bio'] ?? null) ? $data['bio'] : null,
            'show_karma' => (bool) ($data['show_karma'] ?? false),
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

    public function destroy(
        Request $request,
        CommunityAvatarService $avatars,
        CommunityKarmaService $karma,
    ): RedirectResponse {
        $request->validate(['confirmation' => ['required', 'in:УДАЛИТЬ']]);
        /** @var CommunityUser $user */
        $user = auth('community')->user();
        $avatarPath = $user->avatar_path;
        $affectedKarmaUserIds = CommunityPost::query()
            ->whereIn('id', CommunityPostVote::query()->where('community_user_id', $user->id)->select('community_post_id'))
            ->pluck('community_user_id')
            ->merge(CommunityComment::query()
                ->whereIn('id', CommunityCommentVote::query()->where('community_user_id', $user->id)->select('community_comment_id'))
                ->pluck('community_user_id'));

        foreach (['community_reactions', 'community_awards'] as $table) {
            $targets = DB::table($table)->where('community_user_id', $user->id)->get(['target_type', 'target_id']);
            $affectedKarmaUserIds = $affectedKarmaUserIds
                ->merge(CommunityPost::query()->whereIn('id', $targets->where('target_type', 'post')->pluck('target_id'))->pluck('community_user_id'))
                ->merge(CommunityComment::query()->whereIn('id', $targets->where('target_type', 'comment')->pluck('target_id'))->pluck('community_user_id'));
        }

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
        $karma->recalculateMany($affectedKarmaUserIds);
        $avatars->deletePath($avatarPath);

        auth('community')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('community.index')->with('status', 'Аккаунт удалён, публикации обезличены.');
    }
}
