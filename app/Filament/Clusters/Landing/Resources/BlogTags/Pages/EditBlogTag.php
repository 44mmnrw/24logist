<?php

namespace App\Filament\Clusters\Landing\Resources\BlogTags\Pages;

use App\Filament\Clusters\Landing\Resources\BlogTags\BlogTagResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBlogTag extends EditRecord
{
    protected static string $resource = BlogTagResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->visible(fn (): bool => ! $this->record->isUsed()),
        ];
    }
}
