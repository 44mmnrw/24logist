<?php

namespace App\Filament\Resources\ReferralTerms\Pages;

use App\Filament\Resources\ReferralTerms\ReferralTermResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListReferralTerms extends ListRecords
{
    protected static string $resource = ReferralTermResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
