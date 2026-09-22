<?php

namespace App\Filament\Resources\CommunitySeoPages\Pages;

use App\Filament\Resources\CommunitySeoPages\CommunitySeoPageResource;
use App\Services\Community\CommunitySeoRegistry;
use Filament\Resources\Pages\ListRecords;

class ListCommunitySeoPages extends ListRecords
{
    protected static string $resource = CommunitySeoPageResource::class;

    protected static ?string $title = 'SEO страниц сообщества';

    public function mount(): void
    {
        parent::mount();
        app(CommunitySeoRegistry::class)->sync();
    }
}
