<?php

namespace App\Filament\Resources\CommunityPosts\Pages;

use App\Filament\Resources\CommunityPosts\CommunityPostResource;
use App\Models\CommunityModerationAction;
use Filament\Resources\Pages\EditRecord;

class EditCommunityPost extends EditRecord
{
    protected static string $resource = CommunityPostResource::class;

    protected function afterSave(): void
    {
        CommunityModerationAction::query()->create(['admin_user_id' => auth()->id(), 'target_type' => 'post', 'target_id' => $this->getRecord()->getKey(), 'action' => 'admin_edit', 'metadata' => ['changes' => array_keys($this->getRecord()->getChanges())]]);
    }
}
