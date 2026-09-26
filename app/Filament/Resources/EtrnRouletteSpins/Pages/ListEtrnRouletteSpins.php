<?php

namespace App\Filament\Resources\EtrnRouletteSpins\Pages;

use App\Filament\Resources\EtrnRouletteSpins\EtrnRouletteSpinResource;
use App\Filament\Resources\EtrnRouletteSpins\Widgets\EtrnRouletteStats;
use Filament\Resources\Pages\ListRecords;

class ListEtrnRouletteSpins extends ListRecords
{
    protected static string $resource = EtrnRouletteSpinResource::class;

    protected function getHeaderWidgets(): array
    {
        return [EtrnRouletteStats::class];
    }
}
