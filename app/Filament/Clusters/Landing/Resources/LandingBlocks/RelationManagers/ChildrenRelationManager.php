<?php

namespace App\Filament\Clusters\Landing\Resources\LandingBlocks\RelationManagers;

use App\Filament\Clusters\Landing\Resources\LandingBlocks\LandingBlockResource;
use App\Models\LandingBlock;
use App\Services\LandingPageService;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ChildrenRelationManager extends RelationManager
{
    protected static string $relationship = 'children';

    protected static ?string $title = 'Вложенные блоки';

    protected static ?string $modelLabel = 'вложенный блок';

    protected static ?string $pluralModelLabel = 'Вложенные блоки';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        if ($ownerRecord instanceof LandingBlock && in_array($ownerRecord->block_type, ['question', 'footer_column'], true)) {
            return false;
        }

        return parent::canViewForRecord($ownerRecord, $pageClass);
    }

    public function form(Schema $schema): Schema
    {
        return LandingBlockResource::form($schema);
    }

    public function table(Table $table): Table
    {
        return LandingBlockResource::table($table)
            ->headerActions([
                CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $owner = $this->getOwnerRecord();
                        $data['section_slug'] = $owner->section_slug;
                        $data['parent_id'] = $owner->id;

                        return $data;
                    })
                    ->after(fn () => app(LandingPageService::class)->clearCache()),
            ])
            ->recordActions([
                EditAction::make()
                    ->after(fn () => app(LandingPageService::class)->clearCache()),
                DeleteAction::make()
                    ->after(fn () => app(LandingPageService::class)->clearCache()),
            ]);
    }
}
