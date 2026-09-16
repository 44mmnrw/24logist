<?php

namespace App\Filament\Resources\ReferralParticipants\Pages;

use App\Filament\Resources\ReferralParticipants\ReferralParticipantResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListReferralParticipants extends ListRecords
{
    protected static string $resource = ReferralParticipantResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
