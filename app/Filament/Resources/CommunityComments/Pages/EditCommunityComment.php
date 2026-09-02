<?php

namespace App\Filament\Resources\CommunityComments\Pages;

use App\Filament\Resources\CommunityComments\CommunityCommentResource;
use App\Models\CommunityModerationAction;
use Filament\Resources\Pages\EditRecord;

class EditCommunityComment extends EditRecord
{
    protected static string $resource = CommunityCommentResource::class;

    protected function afterSave(): void
    {
        CommunityModerationAction::query()->create(['admin_user_id' => auth()->id(), 'target_type' => 'comment', 'target_id' => $this->getRecord()->getKey(), 'action' => 'admin_edit', 'metadata' => ['changes' => array_keys($this->getRecord()->getChanges())]]);
    }
}
