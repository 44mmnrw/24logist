<?php

namespace App\Filament\Resources\CommunityCategories\Pages;

use App\Filament\Resources\CommunityCategories\CommunityCategoryResource;
use Filament\Resources\Pages\EditRecord;

class EditCommunityCategory extends EditRecord
{
    protected static string $resource = CommunityCategoryResource::class;
}
