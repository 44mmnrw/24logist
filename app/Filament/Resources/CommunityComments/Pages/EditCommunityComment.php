<?php

namespace App\Filament\Resources\CommunityComments\Pages;

use App\Filament\Resources\CommunityComments\CommunityCommentResource;
use App\Models\CommunityModerationAction;
use App\Services\Community\CommunityContentRenderer;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditCommunityComment extends EditRecord
{
    protected static string $resource = CommunityCommentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()->label('Просмотр и жалобы'),
            ActionGroup::make(CommunityCommentResource::moderationActions())
                ->label('Модерация'),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $body = trim((string) ($data['body_markdown'] ?? ''));

        if ($body === '' && ! $this->getRecord()->photos()->exists()) {
            throw ValidationException::withMessages([
                'data.body_markdown' => 'У комментария должен остаться текст или фотография.',
            ]);
        }

        $data['body_markdown'] = $body === '' ? null : $body;
        $data['body_html'] = app(CommunityContentRenderer::class)->render($body);
        $data['edited_at'] = now();

        return $data;
    }

    protected function afterSave(): void
    {
        CommunityModerationAction::query()->create([
            'admin_user_id' => Filament::auth()->id(),
            'target_type' => 'comment',
            'target_id' => $this->getRecord()->getKey(),
            'action' => 'admin_edit',
            'metadata' => ['changes' => array_keys($this->getRecord()->getChanges())],
        ]);
    }
}
