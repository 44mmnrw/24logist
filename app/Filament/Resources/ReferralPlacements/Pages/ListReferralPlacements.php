<?php

namespace App\Filament\Resources\ReferralPlacements\Pages;

use App\Filament\Resources\ReferralPlacements\ReferralPlacementResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListReferralPlacements extends ListRecords
{
    protected static string $resource = ReferralPlacementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            Action::make('export')->label('Экспорт для ОРД')->url('/admin/referrals/placements/export.csv')->openUrlInNewTab(),
        ];
    }
}
