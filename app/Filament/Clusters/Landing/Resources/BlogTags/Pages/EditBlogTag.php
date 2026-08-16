<?php

namespace App\Filament\Clusters\Landing\Resources\BlogTags\Pages;

use App\Filament\Clusters\Landing\Resources\BlogTags\BlogTagResource;
use Filament\Resources\Pages\EditRecord;

class EditBlogTag extends EditRecord
{
    protected static string $resource = BlogTagResource::class;
}
