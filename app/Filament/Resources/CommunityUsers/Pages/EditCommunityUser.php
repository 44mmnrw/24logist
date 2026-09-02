<?php

namespace App\Filament\Resources\CommunityUsers\Pages;

use App\Filament\Resources\CommunityUsers\CommunityUserResource;
use App\Models\CommunityModerationAction;
use Filament\Resources\Pages\EditRecord;

class EditCommunityUser extends EditRecord
{
    protected static string $resource = CommunityUserResource::class;

    protected function afterSave(): void
    {
        CommunityModerationAction::query()->create(['admin_user_id' => auth()->id(), 'target_type' => 'user', 'target_id' => $this->getRecord()->getKey(), 'action' => 'admin_edit', 'metadata' => ['changes' => array_keys($this->getRecord()->getChanges())]]);
    }
}
