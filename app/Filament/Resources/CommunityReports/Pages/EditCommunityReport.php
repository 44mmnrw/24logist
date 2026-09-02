<?php

namespace App\Filament\Resources\CommunityReports\Pages;

use App\Filament\Resources\CommunityReports\CommunityReportResource;
use App\Models\CommunityModerationAction;
use Filament\Resources\Pages\EditRecord;

class EditCommunityReport extends EditRecord
{
    protected static string $resource = CommunityReportResource::class;

    protected function afterSave(): void
    {
        CommunityModerationAction::query()->create(['admin_user_id' => auth()->id(), 'target_type' => 'report', 'target_id' => $this->getRecord()->getKey(), 'action' => 'admin_edit', 'metadata' => ['changes' => array_keys($this->getRecord()->getChanges())]]);
    }
}
