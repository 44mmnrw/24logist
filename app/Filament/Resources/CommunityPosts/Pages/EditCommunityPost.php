<?php

namespace App\Filament\Resources\CommunityPosts\Pages;

use App\Filament\Resources\CommunityPosts\CommunityPostResource;
use App\Models\CommunityModerationAction;
use App\Services\Community\CommunityContentRenderer;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditCommunityPost extends EditRecord
{
    protected static string $resource = CommunityPostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()->label('Просмотр и жалобы'),
            ActionGroup::make(CommunityPostResource::moderationActions())
                ->label('Модерация'),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $body = trim((string) ($data['body_markdown'] ?? ''));
        $externalUrl = trim((string) ($data['external_url'] ?? ''));

        if ($body !== '' && $externalUrl !== '') {
            throw ValidationException::withMessages([
                'data.body_markdown' => 'Оставьте либо текст публикации, либо внешнюю ссылку.',
            ]);
        }

        if ($body === '' && $externalUrl === '' && ! $this->getRecord()->photos()->exists()) {
            throw ValidationException::withMessages([
                'data.body_markdown' => 'У темы должен остаться текст, внешняя ссылка или фотография.',
            ]);
        }

        $data['title'] = trim((string) $data['title']);
        $data['body_markdown'] = $body === '' ? null : $body;
        $data['body_html'] = app(CommunityContentRenderer::class)->render($body);
        $data['external_url'] = $externalUrl === '' ? null : $externalUrl;
        $data['edited_at'] = now();

        $seoFields = [
            'meta_title', 'meta_description', 'meta_keywords', 'meta_robots', 'canonical_url',
            'og_title', 'og_description', 'twitter_title', 'twitter_description', 'twitter_card',
        ];
        $data['seo_is_custom'] = $this->getRecord()->seo_is_custom
            || collect($seoFields)->contains(function (string $field) use ($data): bool {
                return array_key_exists($field, $data) && (string) ($data[$field] ?? '') !== (string) $this->getRecord()->getOriginal($field);
            });

        return $data;
    }

    protected function afterSave(): void
    {
        CommunityModerationAction::query()->create([
            'admin_user_id' => Filament::auth()->id(),
            'target_type' => 'post',
            'target_id' => $this->getRecord()->getKey(),
            'action' => 'admin_edit',
            'metadata' => ['changes' => array_keys($this->getRecord()->getChanges())],
        ]);
    }
}
