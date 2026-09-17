<?php

namespace App\Filament\Resources\CommunityPosts\Pages;

use App\Filament\Resources\CommunityPosts\CommunityPostResource;
use App\Models\CommunityAiPersona;
use App\Models\CommunityCategory;
use App\Models\CommunityPost;
use App\Models\CommunityPostVote;
use App\Services\Community\CommunityContentRenderer;
use App\Services\Community\CommunityRanking;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CreateCommunityPost extends CreateRecord
{
    protected static string $resource = CommunityPostResource::class;

    protected static bool $canCreateAnother = false;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $persona = CommunityAiPersona::query()
            ->where('community_user_id', $data['community_user_id'] ?? null)
            ->where('is_active', true)
            ->where('can_create_posts', true)
            ->whereHas('communityUser', fn ($query) => $query
                ->whereNull('deleted_at')
                ->whereNull('banned_at')
                ->where(fn ($query) => $query
                    ->whereNull('suspended_until')
                    ->orWhere('suspended_until', '<=', now())))
            ->first();

        if ($persona === null) {
            throw ValidationException::withMessages([
                'data.community_user_id' => 'Выбранная персона недоступна для публикации.',
            ]);
        }

        $categoryExists = CommunityCategory::query()
            ->active()
            ->where('posting_enabled', true)
            ->whereKey($data['community_category_id'] ?? null)
            ->exists();
        if (! $categoryExists) {
            throw ValidationException::withMessages([
                'data.community_category_id' => 'Выбранная рубрика недоступна для публикации.',
            ]);
        }

        $body = trim((string) ($data['body_markdown'] ?? ''));
        $externalUrl = trim((string) ($data['external_url'] ?? ''));
        if ($body === '' && $externalUrl === '') {
            throw ValidationException::withMessages([
                'data.body_markdown' => 'Добавьте текст или внешнюю ссылку.',
            ]);
        }
        if ($body !== '' && $externalUrl !== '') {
            throw ValidationException::withMessages([
                'data.body_markdown' => 'Выберите текст публикации или внешнюю ссылку.',
            ]);
        }

        $publishedAt = Carbon::parse($data['published_at'] ?? now());
        if ($publishedAt->isFuture()) {
            throw ValidationException::withMessages([
                'data.published_at' => 'Для будущей публикации используйте AI-сценарий с расписанием.',
            ]);
        }

        $data['title'] = trim((string) $data['title']);
        $data['slug'] = Str::slug($data['title']) ?: 'topic';
        $data['body_markdown'] = $body !== '' ? $body : null;
        $data['body_html'] = app(CommunityContentRenderer::class)->render($body);
        $data['external_url'] = $externalUrl !== '' ? $externalUrl : null;
        $data['status'] = CommunityPost::STATUS_PUBLISHED;
        $data['published_at'] = $publishedAt;
        $data['hot_score'] = CommunityRanking::hotScore(1, $publishedAt);
        $data['seo_is_custom'] = collect([
            'meta_title', 'meta_description', 'meta_keywords', 'meta_robots', 'canonical_url',
            'og_title', 'og_description', 'twitter_title', 'twitter_description', 'twitter_card',
        ])->contains(fn (string $field): bool => filled($data[$field] ?? null));

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        $record = parent::handleRecordCreation($data);
        $record->forceFill(['created_at' => $record->published_at])->saveQuietly();

        return $record;
    }

    protected function afterCreate(): void
    {
        CommunityPostVote::query()->firstOrCreate([
            'community_user_id' => $this->record->community_user_id,
            'community_post_id' => $this->record->id,
        ], ['value' => 1]);

        CommunityAiPersona::query()
            ->where('community_user_id', $this->record->community_user_id)
            ->update(['last_acted_at' => now()]);
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Тема опубликована';
    }
}
