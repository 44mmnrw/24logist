<?php

namespace App\Filament\Clusters\Landing\Resources\LandingSections\RelationManagers;

use App\Filament\Clusters\Landing\Resources\LandingBlocks\LandingBlockResource;
use App\Models\LandingBlock;
use App\Models\LandingSection;
use App\Services\LandingPageService;
use App\Support\LandingFooter;
use App\Support\LandingIcons;
use App\Support\LandingPlatform;
use App\Support\LandingPricing;
use App\Support\LandingQuiz;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class BlocksRelationManager extends RelationManager
{
    protected static string $relationship = 'blocks';

    protected static ?string $title = 'Блоки секции';

    protected static ?string $modelLabel = 'блок';

    protected static ?string $pluralModelLabel = 'Блоки';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        if (! $ownerRecord instanceof LandingSection) {
            return static::$title ?? 'Блоки секции';
        }

        return match ($ownerRecord->slug) {
            'quiz' => 'Вопросы квиза',
            'faq' => 'Вопросы FAQ',
            'mobile' => 'Пункты списка',
            'hero' => 'Пункты списка',
            'platform' => 'Карточки платформы',
            'footer' => 'Колонки подвала',
            'pricing' => 'Тарифы',
            default => static::$title ?? 'Блоки секции',
        };
    }

    public function form(Schema $schema): Schema
    {
        if ($this->isQuizSection()) {
            return $schema
                ->components([
                    TextInput::make('title')
                        ->label('Вопрос')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),
                    Repeater::make('quiz_options')
                        ->label('Варианты ответа')
                        ->schema([
                            TextInput::make('title')
                                ->label('Вариант')
                                ->required()
                                ->maxLength(255),
                        ])
                        ->minItems(2)
                        ->defaultItems(2)
                        ->addActionLabel('Добавить вариант')
                        ->reorderable()
                        ->columnSpanFull(),
                    Toggle::make('is_active')
                        ->label('Активен')
                        ->default(true),
                    TextInput::make('sort_order')
                        ->label('Порядок')
                        ->numeric()
                        ->default(0),
                ]);
        }

        if ($this->isFaqSection()) {
            return $schema
                ->components([
                    TextInput::make('title')
                        ->label('Вопрос')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),
                    Textarea::make('description')
                        ->label('Ответ')
                        ->required()
                        ->rows(4)
                        ->columnSpanFull(),
                    Toggle::make('is_active')
                        ->label('Активен')
                        ->default(true),
                    TextInput::make('sort_order')
                        ->label('Порядок')
                        ->numeric()
                        ->default(0),
                ]);
        }

        if ($this->isMobileSection()) {
            return $schema
                ->components([
                    TextInput::make('title')
                        ->label('Текст пункта')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),
                    Select::make('icon')
                        ->label('Иконка')
                        ->options(LandingIcons::OPTIONS)
                        ->searchable()
                        ->dehydrateStateUsing(fn (?string $state) => LandingIcons::normalize($state))
                        ->formatStateUsing(fn (?string $state) => LandingIcons::resolve($state)),
                    Toggle::make('is_active')
                        ->label('Активен')
                        ->default(true),
                    TextInput::make('sort_order')
                        ->label('Порядок')
                        ->numeric()
                        ->default(0),
                ]);
        }

        if ($this->isPlatformSection()) {
            return $schema
                ->components([
                    TextInput::make('title')
                        ->label('Заголовок карточки')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),
                    TextInput::make('subtitle')
                        ->label('Номер/лейбл этапа')
                        ->maxLength(255),
                    Textarea::make('description')
                        ->label('Описание')
                        ->rows(3)
                        ->maxLength(2000)
                        ->columnSpanFull(),
                    Select::make('icon')
                        ->label('Иконка')
                        ->options(LandingIcons::OPTIONS)
                        ->searchable()
                        ->dehydrateStateUsing(fn (?string $state) => LandingIcons::normalize($state))
                        ->formatStateUsing(fn (?string $state) => LandingIcons::resolve($state)),
                    TextInput::make('tag')
                        ->label('Тег карточки')
                        ->maxLength(255),
                    Repeater::make('platform_roles')
                        ->label('Роли (platform-roles)')
                        ->schema([
                            TextInput::make('title')
                                ->label('Роль')
                                ->required()
                                ->maxLength(255),
                            TextInput::make('subtitle')
                                ->label('Описание роли')
                                ->maxLength(255),
                        ])
                        ->defaultItems(1)
                        ->addActionLabel('Добавить роль')
                        ->reorderable()
                        ->columnSpanFull(),
                    Toggle::make('is_active')
                        ->label('Активен')
                        ->default(true),
                    TextInput::make('sort_order')
                        ->label('Порядок')
                        ->numeric()
                        ->default(0),
                ]);
        }

        if ($this->isPricingSection()) {
            return $schema
                ->components([
                    TextInput::make('title')
                        ->label('Название тарифа')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),
                    TextInput::make('subtitle')
                        ->label('Описание под названием')
                        ->maxLength(255)
                        ->columnSpanFull(),
                    TextInput::make('price')
                        ->label('Цена')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('tag')
                        ->label('Тег на карточке')
                        ->maxLength(255)
                        ->placeholder('Хит'),
                    TextInput::make('button_text')
                        ->label('Текст кнопки')
                        ->maxLength(255),
                    Select::make('button_style')
                        ->label('Стиль кнопки')
                        ->options([
                            'primary' => 'Primary',
                            'ghost' => 'Ghost',
                        ])
                        ->default('ghost'),
                    Toggle::make('is_highlighted')
                        ->label('Выделить карточку')
                        ->default(false),
                    Repeater::make('plan_features')
                        ->label('Пункты списка')
                        ->schema([
                            TextInput::make('title')
                                ->label('Пункт')
                                ->required()
                                ->maxLength(255),
                            Select::make('icon')
                                ->label('Иконка')
                                ->options(LandingIcons::OPTIONS)
                                ->searchable()
                                ->default('check'),
                        ])
                        ->defaultItems(1)
                        ->minItems(1)
                        ->addActionLabel('Добавить пункт')
                        ->reorderable()
                        ->columnSpanFull(),
                    Toggle::make('is_active')
                        ->label('Активен')
                        ->default(true),
                    TextInput::make('sort_order')
                        ->label('Порядок')
                        ->numeric()
                        ->default(0),
                ]);
        }

        if ($this->isFooterSection()) {
            return $schema
                ->components([
                    TextInput::make('title')
                        ->label('Заголовок колонки')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),
                    Repeater::make('footer_links')
                        ->label('Ссылки')
                        ->schema([
                            TextInput::make('title')
                                ->label('Текст')
                                ->required()
                                ->maxLength(255),
                            TextInput::make('link')
                                ->label('URL')
                                ->maxLength(255)
                                ->placeholder('#features или mailto:hello@example.com'),
                            Select::make('icon')
                                ->label('Иконка')
                                ->options(LandingIcons::OPTIONS)
                                ->searchable()
                                ->nullable(),
                        ])
                        ->defaultItems(1)
                        ->addActionLabel('Добавить ссылку')
                        ->reorderable()
                        ->columnSpanFull(),
                    Toggle::make('is_active')
                        ->label('Активна')
                        ->default(true),
                    TextInput::make('sort_order')
                        ->label('Порядок')
                        ->numeric()
                        ->default(0),
                ]);
        }

        return LandingBlockResource::form($schema);
    }

    public function table(Table $table): Table
    {
        $isQuiz = $this->isQuizSection();
        $isFaq = $this->isFaqSection();
        $isMobile = $this->isMobileSection();
        $isPlatform = $this->isPlatformSection();
        $isFooter = $this->isFooterSection();
        $isPricing = $this->isPricingSection();

        $table = $isQuiz
            ? $table
                ->columns([
                    TextColumn::make('title')
                        ->label('Вопрос')
                        ->searchable()
                        ->limit(60),
                    TextColumn::make('options_count')
                        ->label('Вариантов')
                        ->counts('children'),
                    IconColumn::make('is_active')
                        ->label('Активен')
                        ->boolean(),
                    TextColumn::make('sort_order')
                        ->label('Порядок')
                        ->sortable(),
                ])
                ->defaultSort('sort_order')
            : ($isFaq
                ? $table
                    ->columns([
                        TextColumn::make('title')
                            ->label('Вопрос')
                            ->searchable()
                            ->limit(50),
                        TextColumn::make('description')
                            ->label('Ответ')
                            ->searchable()
                            ->limit(60)
                            ->placeholder('—'),
                        IconColumn::make('is_active')
                            ->label('Активен')
                            ->boolean(),
                        TextColumn::make('sort_order')
                            ->label('Порядок')
                            ->sortable(),
                    ])
                    ->defaultSort('sort_order')
                : ($isMobile
                    ? $table
                        ->columns([
                            TextColumn::make('title')
                                ->label('Пункт')
                                ->searchable()
                                ->limit(60),
                            TextColumn::make('icon')
                                ->label('Иконка')
                                ->formatStateUsing(fn (?string $state) => LandingIcons::resolve($state) ?? '—'),
                            IconColumn::make('is_active')
                                ->label('Активен')
                                ->boolean(),
                            TextColumn::make('sort_order')
                                ->label('Порядок')
                                ->sortable(),
                        ])
                        ->defaultSort('sort_order')
                    : ($isPlatform
                        ? $table
                            ->columns([
                                TextColumn::make('title')
                                    ->label('Карточка')
                                    ->searchable()
                                    ->limit(40),
                                TextColumn::make('subtitle')
                                    ->label('Этап')
                                    ->limit(24),
                                TextColumn::make('roles_count')
                                    ->label('Ролей')
                                    ->counts('children'),
                                IconColumn::make('is_active')
                                    ->label('Активен')
                                    ->boolean(),
                                TextColumn::make('sort_order')
                                    ->label('Порядок')
                                    ->sortable(),
                            ])
                            ->defaultSort('sort_order')
                    : ($isFooter
                        ? $table
                            ->columns([
                                TextColumn::make('title')
                                    ->label('Колонка')
                                    ->searchable()
                                    ->limit(40),
                                TextColumn::make('links_count')
                                    ->label('Ссылок')
                                    ->counts('children'),
                                IconColumn::make('is_active')
                                    ->label('Активна')
                                    ->boolean(),
                                TextColumn::make('sort_order')
                                    ->label('Порядок')
                                    ->sortable(),
                            ])
                            ->defaultSort('sort_order')
                        : ($isPricing
                            ? $table
                                ->columns([
                                    TextColumn::make('title')
                                        ->label('Тариф')
                                        ->searchable()
                                        ->limit(40),
                                    TextColumn::make('price')
                                        ->label('Цена')
                                        ->limit(30),
                                    TextColumn::make('features_count')
                                        ->label('Пунктов')
                                        ->counts('children'),
                                    IconColumn::make('is_highlighted')
                                        ->label('Хит')
                                        ->boolean(),
                                    IconColumn::make('is_active')
                                        ->label('Активен')
                                        ->boolean(),
                                    TextColumn::make('sort_order')
                                        ->label('Порядок')
                                        ->sortable(),
                                ])
                                ->defaultSort('sort_order')
                            : LandingBlockResource::table($table))))));

        return $table
            ->modifyQueryUsing(function ($query) use ($isMobile, $isPlatform, $isFooter, $isPricing): void {
                if ($isMobile) {
                    $query->where('block_type', 'bullet');
                }

                if ($isPlatform) {
                    $query->where('block_type', 'card');
                }

                if ($isFooter) {
                    $query->where('block_type', 'footer_column');
                }

                if ($isPricing) {
                    $query->where('block_type', 'plan');
                }
            })
            ->headerActions([
                CreateAction::make()
                    ->label(match (true) {
                        $isQuiz => 'Добавить вопрос',
                        $isFaq => 'Добавить вопрос',
                        $isMobile => 'Добавить пункт',
                        $isPlatform => 'Добавить карточку',
                        $isFooter => 'Добавить колонку',
                        $isPricing => 'Добавить тариф',
                        default => null,
                    })
                    ->mutateFormDataUsing(function (array $data) use ($isQuiz, $isFaq, $isMobile, $isPlatform, $isFooter, $isPricing): array {
                        $data['section_slug'] = $this->getOwnerRecord()->slug;

                        if ($isQuiz) {
                            $data['block_type'] = 'question';
                        }

                        if ($isFaq) {
                            $data['block_type'] = 'faq';
                        }

                        if ($isMobile) {
                            $data['block_type'] = 'bullet';
                        }

                        if ($isPlatform) {
                            $data['block_type'] = 'card';
                        }

                        if ($isFooter) {
                            $data['block_type'] = 'footer_column';
                        }

                        if ($isPricing) {
                            $data['block_type'] = 'plan';
                        }

                        return $data;
                    })
                    ->using(function (array $data) use ($isQuiz, $isPlatform, $isFooter, $isPricing): Model {
                        if ($isQuiz) {
                            $options = $data['quiz_options'] ?? [];
                            unset($data['quiz_options']);

                            $question = LandingBlock::query()->create([
                                'section_slug' => $this->getOwnerRecord()->slug,
                                'block_type' => 'question',
                                'title' => $data['title'] ?? '',
                                'sort_order' => $data['sort_order'] ?? 0,
                                'is_active' => $data['is_active'] ?? true,
                            ]);

                            LandingQuiz::syncOptions($question, $options);

                            return $question;
                        }

                        if ($isPlatform) {
                            $roles = $data['platform_roles'] ?? [];
                            unset($data['platform_roles']);

                            $card = LandingBlock::query()->create([
                                'section_slug' => $this->getOwnerRecord()->slug,
                                'block_type' => 'card',
                                'title' => $data['title'] ?? '',
                                'subtitle' => $data['subtitle'] ?? null,
                                'description' => $data['description'] ?? null,
                                'icon' => $data['icon'] ?? null,
                                'tag' => $data['tag'] ?? null,
                                'sort_order' => $data['sort_order'] ?? 0,
                                'is_active' => $data['is_active'] ?? true,
                            ]);

                            LandingPlatform::syncRoles($card, $roles);

                            return $card;
                        }

                        if ($isFooter) {
                            $links = $data['footer_links'] ?? [];
                            unset($data['footer_links']);

                            $column = LandingBlock::query()->create([
                                'section_slug' => $this->getOwnerRecord()->slug,
                                'block_type' => 'footer_column',
                                'title' => $data['title'] ?? '',
                                'sort_order' => $data['sort_order'] ?? 0,
                                'is_active' => $data['is_active'] ?? true,
                            ]);

                            LandingFooter::syncLinks($column, $links);

                            return $column;
                        }

                        if ($isPricing) {
                            $features = $data['plan_features'] ?? [];
                            unset($data['plan_features']);

                            $plan = LandingBlock::query()->create([
                                'section_slug' => $this->getOwnerRecord()->slug,
                                'block_type' => 'plan',
                                'title' => $data['title'] ?? '',
                                'subtitle' => $data['subtitle'] ?? null,
                                'price' => $data['price'] ?? '',
                                'tag' => $data['tag'] ?? null,
                                'button_text' => $data['button_text'] ?? null,
                                'button_style' => $data['button_style'] ?? 'ghost',
                                'is_highlighted' => $data['is_highlighted'] ?? false,
                                'sort_order' => $data['sort_order'] ?? 0,
                                'is_active' => $data['is_active'] ?? true,
                            ]);

                            LandingPricing::syncFeatures($plan, $features);

                            return $plan;
                        }

                        $record = new LandingBlock;
                        $record->fill($data);
                        $this->getRelationship()->save($record);

                        return $record;
                    })
                    ->after(fn () => app(LandingPageService::class)->clearCache()),
            ])
            ->recordActions([
                EditAction::make()
                    ->mutateRecordDataUsing(function (array $data, LandingBlock $record) use ($isQuiz, $isPlatform, $isFooter, $isPricing): array {
                        if ($isQuiz && $record->block_type === 'question') {
                            $data['quiz_options'] = LandingQuiz::optionsFormState($record);
                        }

                        if ($isPlatform && $record->block_type === 'card') {
                            $data['platform_roles'] = LandingPlatform::rolesFormState($record);
                        }

                        if ($isFooter && $record->block_type === 'footer_column') {
                            $data['footer_links'] = LandingFooter::linksFormState($record);
                        }

                        if ($isPricing && $record->block_type === 'plan') {
                            $data['plan_features'] = LandingPricing::featuresFormState($record);
                        }

                        return $data;
                    })
                    ->using(function (array $data, LandingBlock $record) use ($isQuiz, $isPlatform, $isFooter, $isPricing): void {
                        if ($isQuiz && $record->block_type === 'question') {
                            $options = $data['quiz_options'] ?? [];
                            $record->update([
                                'title' => $data['title'] ?? $record->title,
                                'sort_order' => $data['sort_order'] ?? $record->sort_order,
                                'is_active' => $data['is_active'] ?? $record->is_active,
                            ]);

                            LandingQuiz::syncOptions($record, $options);

                            return;
                        }

                        if ($isPlatform && $record->block_type === 'card') {
                            $roles = $data['platform_roles'] ?? [];
                            $record->update([
                                'title' => $data['title'] ?? $record->title,
                                'subtitle' => $data['subtitle'] ?? $record->subtitle,
                                'description' => $data['description'] ?? $record->description,
                                'icon' => $data['icon'] ?? $record->icon,
                                'tag' => $data['tag'] ?? $record->tag,
                                'sort_order' => $data['sort_order'] ?? $record->sort_order,
                                'is_active' => $data['is_active'] ?? $record->is_active,
                            ]);

                            LandingPlatform::syncRoles($record, $roles);

                            return;
                        }

                        if ($isFooter && $record->block_type === 'footer_column') {
                            $links = $data['footer_links'] ?? [];
                            $record->update([
                                'title' => $data['title'] ?? $record->title,
                                'sort_order' => $data['sort_order'] ?? $record->sort_order,
                                'is_active' => $data['is_active'] ?? $record->is_active,
                            ]);

                            LandingFooter::syncLinks($record, $links);

                            return;
                        }

                        if ($isPricing && $record->block_type === 'plan') {
                            $features = $data['plan_features'] ?? [];
                            $record->update([
                                'title' => $data['title'] ?? $record->title,
                                'subtitle' => $data['subtitle'] ?? $record->subtitle,
                                'price' => $data['price'] ?? $record->price,
                                'tag' => $data['tag'] ?? $record->tag,
                                'button_text' => $data['button_text'] ?? $record->button_text,
                                'button_style' => $data['button_style'] ?? $record->button_style,
                                'is_highlighted' => $data['is_highlighted'] ?? $record->is_highlighted,
                                'sort_order' => $data['sort_order'] ?? $record->sort_order,
                                'is_active' => $data['is_active'] ?? $record->is_active,
                            ]);

                            LandingPricing::syncFeatures($record, $features);

                            return;
                        }

                        $record->update(
                            LandingPricing::stripVirtualFields(
                                LandingFooter::stripVirtualFields(
                                    LandingPlatform::stripVirtualFields(
                                        LandingQuiz::stripVirtualFields($data),
                                    ),
                                ),
                            ),
                        );
                    })
                    ->after(fn () => app(LandingPageService::class)->clearCache()),
                DeleteAction::make()
                    ->before(function (LandingBlock $record): void {
                        if ($record->block_type === 'question') {
                            $record->children()->where('block_type', 'option')->delete();
                        }

                        if ($record->block_type === 'card') {
                            $record->children()->where('block_type', 'role')->delete();
                        }

                        if ($record->block_type === 'footer_column') {
                            $record->children()->where('block_type', 'footer_link')->delete();
                        }

                        if ($record->block_type === 'plan') {
                            $record->children()->where('block_type', 'feature')->delete();
                        }
                    })
                    ->after(fn () => app(LandingPageService::class)->clearCache()),
            ]);
    }

    protected function isQuizSection(): bool
    {
        return $this->getOwnerRecord()->slug === 'quiz';
    }

    protected function isFaqSection(): bool
    {
        return $this->getOwnerRecord()->slug === 'faq';
    }

    protected function isMobileSection(): bool
    {
        return $this->getOwnerRecord()->slug === 'mobile';
    }

    protected function isPlatformSection(): bool
    {
        return $this->getOwnerRecord()->slug === 'platform';
    }

    protected function isFooterSection(): bool
    {
        return $this->getOwnerRecord()->slug === 'footer';
    }

    protected function isPricingSection(): bool
    {
        return $this->getOwnerRecord()->slug === 'pricing';
    }
}
