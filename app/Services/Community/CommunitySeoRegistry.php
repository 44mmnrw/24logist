<?php

namespace App\Services\Community;

use App\Models\CommunityCategory;
use App\Models\CommunityPost;
use App\Models\CommunitySeoPage;
use App\Models\CommunityUser;
use Illuminate\Database\Eloquent\Model;

final class CommunitySeoRegistry
{
    public const PAGES = [
        'community.index' => ['Обсуждения сообщества', 'page'],
        'community.rules' => ['Правила сообщества', 'page'],
        'community.privacy' => ['Данные участников сообщества', 'page'],
        'community.login' => ['Войти в сообщество', 'service'],
        'community.register' => ['Зарегистрироваться в сообществе', 'service'],
        'community.onboarding' => ['Создайте публичный профиль', 'service'],
        'community.posts.create' => ['Новая тема', 'service'],
        'community.notifications' => ['Уведомления', 'service'],
        'community.settings' => ['Настройки профиля', 'service'],
        'community.moderation.index' => ['Очередь модерации', 'service'],
    ];

    /** Refresh the inventory without touching manually configured metadata. */
    public function sync(): void
    {
        foreach (self::PAGES as $route => [$label, $kind]) {
            $this->remember($route, $kind, $label, route($route, [], false), $kind === 'page');
        }

        CommunityCategory::query()->each(fn (CommunityCategory $category) => $this->syncSource($category));
        CommunityPost::withTrashed()->each(fn (CommunityPost $post) => $this->syncSource($post));
        CommunityUser::withTrashed()->each(fn (CommunityUser $user) => $this->syncSource($user));
    }

    public function syncSource(Model $source): ?CommunitySeoPage
    {
        if ($source instanceof CommunityUser && blank($source->username)) {
            $page = CommunitySeoPage::query()->where('page_key', 'profile:'.$source->id)->first();
            $page?->update(['is_public' => false]);

            return $page;
        }

        return match (true) {
            $source instanceof CommunityCategory => $this->remember(
                'category:'.$source->id, 'category', $source->name,
                route('community.categories.show', $source, false), $source->is_active ?? true,
            ),
            $source instanceof CommunityPost => $this->remember(
                'post:'.$source->id, 'post', $source->title,
                route('community.posts.show', ['post' => $source->id, 'slug' => $source->slug], false),
                ! $source->trashed() && ($source->status ?? 'published') === 'published',
            ),
            $source instanceof CommunityUser => $this->remember(
                'profile:'.$source->id, 'profile', $source->displayName(),
                route('community.profile', $source, false), ! $source->trashed() && $source->isOnboarded(),
            ),
        };
    }

    private function remember(string $key, string $kind, string $label, string $path, bool $public): CommunitySeoPage
    {
        $page = CommunitySeoPage::query()->firstOrNew(['page_key' => $key]);
        $page->fill(['kind' => $kind, 'label' => $label, 'path' => $path, 'is_public' => $public]);
        if ($page->isDirty()) {
            $page->save();
        }

        return $page;
    }
}
