<?php

namespace App\Filament\Resources\CommunityUsers\Pages;

use App\Filament\Resources\CommunityUsers\CommunityUserResource;
use Filament\Resources\Pages\ListRecords;

class ListCommunityUsers extends ListRecords
{
    protected static string $resource = CommunityUserResource::class;
}
