<?php

namespace App\Filament\Clusters\Landing\Resources\BlogTags\Pages;

use App\Filament\Clusters\Landing\Resources\BlogTags\BlogTagResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBlogTags extends ListRecords
{
    protected static string $resource = BlogTagResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
