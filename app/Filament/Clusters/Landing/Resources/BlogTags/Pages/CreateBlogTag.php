<?php

namespace App\Filament\Clusters\Landing\Resources\BlogTags\Pages;

use App\Filament\Clusters\Landing\Resources\BlogTags\BlogTagResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBlogTag extends CreateRecord
{
    protected static string $resource = BlogTagResource::class;
}
