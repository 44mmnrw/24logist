<?php

namespace App\Filament\Forms;

use App\Support\LandingIcons;
use Filament\Forms\Components\Field;

final class LandingIconSelect extends Field
{
    protected string $view = 'filament.forms.components.landing-icon-select';

    /** @var array<string, string> */
    private const CATEGORIES = [
        'Animals' => 'Животные',
        'Arrows' => 'Стрелки',
        'Badges' => 'Значки',
        'Brand' => 'Бренды',
        'Buildings' => 'Здания',
        'Charts' => 'Диаграммы',
        'Communication' => 'Общение',
        'Computers' => 'Компьютеры',
        'Currencies' => 'Валюты',
        'Database' => 'Базы данных',
        'Design' => 'Дизайн',
        'Development' => 'Разработка',
        'Devices' => 'Устройства',
        'Document' => 'Документы',
        'E-commerce' => 'Торговля',
        'Electrical' => 'Электрика',
        'Extensions' => 'Расширения',
        'Food' => 'Еда',
        'Games' => 'Игры',
        'Gender' => 'Гендер',
        'Gestures' => 'Жесты',
        'Health' => 'Здоровье',
        'Laundry' => 'Уход за одеждой',
        'Letters' => 'Буквы',
        'Logic' => 'Логика',
        'Map' => 'Карты',
        'Math' => 'Математика',
        'Media' => 'Медиа',
        'Mood' => 'Эмоции',
        'Nature' => 'Природа',
        'Numbers' => 'Числа',
        'Photography' => 'Фото',
        'Shapes' => 'Фигуры',
        'Sport' => 'Спорт',
        'Symbols' => 'Символы',
        'System' => 'Система',
        'Text' => 'Текст',
        'Vehicles' => 'Транспорт',
        'Version control' => 'Контроль версий',
        'Weather' => 'Погода',
        'Zodiac' => 'Зодиак',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->helperText('Откройте каталог, чтобы выбрать любую иконку Tabler. Поиск работает на русском и английском.')
            ->dehydrateStateUsing(fn (?string $state): ?string => LandingIcons::normalize($state))
            ->formatStateUsing(fn (?string $state): ?string => LandingIcons::toTabler($state));
    }

    public function getCatalogUrl(): string
    {
        return asset('icons/tabler/v3.46.0/tabler-icons-ru.json');
    }

    public function getOutlineSpriteUrl(): string
    {
        return asset('icons/tabler/v3.46.0/tabler-sprite.svg');
    }

    public function getFilledSpriteUrl(): string
    {
        return asset('icons/tabler/v3.46.0/tabler-sprite-filled.svg');
    }

    /** @return array<string, string> */
    public function getCategories(): array
    {
        $categories = self::CATEGORIES;

        asort($categories, SORT_NATURAL | SORT_FLAG_CASE);

        return $categories;
    }
}
