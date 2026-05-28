<?php

namespace App\Filament\Clusters\Landing\Resources\CmsPages;

use App\Filament\Clusters\Landing\Resources\CmsPages\Pages\CreateCmsPage;
use App\Filament\Clusters\Landing\Resources\CmsPages\Pages\EditCmsPage;
use App\Filament\Clusters\Landing\Resources\CmsPages\Pages\ListCmsPages;
use App\Models\CmsPage;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class CmsPageResource extends Resource
{
    protected static ?string $model = CmsPage::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocument;

    protected static ?string $navigationLabel = 'Страницы';

    protected static ?string $modelLabel = 'страница';

    protected static ?string $pluralModelLabel = 'Страницы';

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->label('Заголовок')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Set $set, Get $get, ?string $state): void {
                        if (filled($get('slug'))) {
                            return;
                        }

                        $set('slug', Str::slug((string) $state));
                    }),
                TextInput::make('slug')
                    ->label('URL-адрес')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->helperText('Страница откроется по адресу /pages/slug'),
                RichEditor::make('body')
                    ->label('Содержимое')
                    ->columnSpanFull(),
                Repeater::make('extra.managers')
                    ->label('Карточки менеджеров (для contacts)')
                    ->schema([
                        TextInput::make('name')
                            ->label('Имя')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('position')
                            ->label('Должность')
                            ->maxLength(255),
                        TextInput::make('phone')
                            ->label('Телефон')
                            ->maxLength(255),
                        TextInput::make('email')
                            ->label('Email')
                            ->maxLength(255),
                        Select::make('color')
                            ->label('Цвет аватара')
                            ->options([
                                'blue' => 'Синий',
                                'dark' => 'Тёмно-синий',
                                'green' => 'Зелёный',
                            ])
                            ->default('blue')
                            ->required(),
                    ])
                    ->defaultItems(3)
                    ->addActionLabel('Добавить менеджера')
                    ->reorderable()
                    ->columnSpanFull()
                    ->visible(fn (Get $get): bool => $get('slug') === 'contacts'),
                TextInput::make('extra.contacts_pill_text')
                    ->label('Contacts: плашка в hero')
                    ->maxLength(255)
                    ->default('Контакты')
                    ->visible(fn (Get $get): bool => $get('slug') === 'contacts'),
                TextInput::make('extra.contacts_hero_subtitle')
                    ->label('Contacts: подзаголовок в hero')
                    ->maxLength(500)
                    ->default('Выберите менеджера или заполните форму — ответим в течение 30 минут в рабочее время.')
                    ->visible(fn (Get $get): bool => $get('slug') === 'contacts'),
                TextInput::make('extra.contacts_managers_title')
                    ->label('Contacts: заголовок блока менеджеров')
                    ->maxLength(255)
                    ->default('Наши менеджеры')
                    ->visible(fn (Get $get): bool => $get('slug') === 'contacts'),
                TextInput::make('extra.contacts_managers_subtitle')
                    ->label('Contacts: подзаголовок блока менеджеров')
                    ->maxLength(500)
                    ->default('Каждый специализируется на своей зоне — обратитесь напрямую.')
                    ->visible(fn (Get $get): bool => $get('slug') === 'contacts'),
                TextInput::make('extra.contacts_call_button_text')
                    ->label('Contacts: текст кнопки звонка')
                    ->maxLength(255)
                    ->default('Позвонить')
                    ->visible(fn (Get $get): bool => $get('slug') === 'contacts'),
                TextInput::make('extra.contacts_empty_managers_title')
                    ->label('Contacts: заголовок при пустом списке менеджеров')
                    ->maxLength(255)
                    ->default('Карточки менеджеров не заполнены')
                    ->visible(fn (Get $get): bool => $get('slug') === 'contacts'),
                TextInput::make('extra.contacts_empty_managers_text')
                    ->label('Contacts: текст при пустом списке менеджеров')
                    ->maxLength(500)
                    ->default('Добавьте их в админке: Страницы -> contacts -> Карточки менеджеров.')
                    ->visible(fn (Get $get): bool => $get('slug') === 'contacts'),
                TextInput::make('extra.contacts_form_title')
                    ->label('Contacts: заголовок формы')
                    ->maxLength(255)
                    ->default('Написать нам')
                    ->visible(fn (Get $get): bool => $get('slug') === 'contacts'),
                TextInput::make('extra.contacts_form_subtitle')
                    ->label('Contacts: подзаголовок формы')
                    ->maxLength(500)
                    ->default('Заполните форму — менеджер свяжется с вами.')
                    ->visible(fn (Get $get): bool => $get('slug') === 'contacts'),
                TextInput::make('extra.contacts_name_label')
                    ->label('Contacts: label поля имени')
                    ->maxLength(255)
                    ->default('Ваше имя')
                    ->visible(fn (Get $get): bool => $get('slug') === 'contacts'),
                TextInput::make('extra.contacts_name_placeholder')
                    ->label('Contacts: placeholder поля имени')
                    ->maxLength(255)
                    ->default('Иван Петров')
                    ->visible(fn (Get $get): bool => $get('slug') === 'contacts'),
                TextInput::make('extra.contacts_phone_label')
                    ->label('Contacts: label поля телефона')
                    ->maxLength(255)
                    ->default('Телефон')
                    ->visible(fn (Get $get): bool => $get('slug') === 'contacts'),
                TextInput::make('extra.contacts_phone_placeholder')
                    ->label('Contacts: placeholder поля телефона')
                    ->maxLength(255)
                    ->default('+7 (___) ___-__-__')
                    ->visible(fn (Get $get): bool => $get('slug') === 'contacts'),
                TextInput::make('extra.contacts_email_label')
                    ->label('Contacts: label поля email')
                    ->maxLength(255)
                    ->default('Электронная почта')
                    ->visible(fn (Get $get): bool => $get('slug') === 'contacts'),
                TextInput::make('extra.contacts_email_placeholder')
                    ->label('Contacts: placeholder поля email')
                    ->maxLength(255)
                    ->default('ivan@company.ru')
                    ->visible(fn (Get $get): bool => $get('slug') === 'contacts'),
                TextInput::make('extra.contacts_message_label')
                    ->label('Contacts: label поля сообщения')
                    ->maxLength(255)
                    ->default('Сообщение')
                    ->visible(fn (Get $get): bool => $get('slug') === 'contacts'),
                TextInput::make('extra.contacts_message_placeholder')
                    ->label('Contacts: placeholder поля сообщения')
                    ->maxLength(500)
                    ->default('Опишите ваш вопрос или задачу...')
                    ->visible(fn (Get $get): bool => $get('slug') === 'contacts'),
                TextInput::make('extra.contacts_submit_text')
                    ->label('Contacts: текст кнопки отправки')
                    ->maxLength(255)
                    ->default('Отправить сообщение')
                    ->visible(fn (Get $get): bool => $get('slug') === 'contacts'),
                TextInput::make('extra.contacts_privacy_prefix')
                    ->label('Contacts: текст перед ссылкой политики')
                    ->maxLength(500)
                    ->default('Нажимая кнопку, вы соглашаетесь с')
                    ->visible(fn (Get $get): bool => $get('slug') === 'contacts'),
                TextInput::make('extra.contacts_privacy_link_text')
                    ->label('Contacts: текст ссылки политики')
                    ->maxLength(255)
                    ->default('политикой конфиденциальности')
                    ->visible(fn (Get $get): bool => $get('slug') === 'contacts'),
                TextInput::make('meta_title')
                    ->label('Meta title')
                    ->maxLength(255)
                    ->helperText('Если пусто — используется заголовок страницы'),
                Textarea::make('meta_description')
                    ->label('Meta description')
                    ->rows(3)
                    ->maxLength(500),
                Toggle::make('is_published')
                    ->label('Опубликована')
                    ->default(true),
                TextInput::make('sort_order')
                    ->label('Порядок')
                    ->numeric()
                    ->default(0),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Заголовок')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('slug')
                    ->label('Slug')
                    ->badge()
                    ->copyable()
                    ->copyMessage('Slug скопирован'),
                TextColumn::make('url')
                    ->label('URL')
                    ->state(fn (CmsPage $record): string => '/pages/'.$record->slug)
                    ->copyable()
                    ->copyableState(fn (CmsPage $record): string => $record->getUrl())
                    ->copyMessage('Ссылка скопирована')
                    ->toggleable(),
                IconColumn::make('is_published')
                    ->label('Опубликована')
                    ->boolean(),
                TextColumn::make('sort_order')
                    ->label('Порядок')
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label('Обновлена')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('sort_order')
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCmsPages::route('/'),
            'create' => CreateCmsPage::route('/create'),
            'edit' => EditCmsPage::route('/{record}/edit'),
        ];
    }
}
