<?php

namespace App\Support;

final class TablerIconTranslations
{
    /** @var array<string, string>|null */
    private static ?array $translations = null;

    /** @var array<string, string> */
    private const TITLE_OVERRIDES = [
        'adjustments' => 'Настройки',
        'brand-github' => 'GitHub',
        'brand-telegram' => 'Telegram',
        'brand-vk' => 'ВКонтакте',
        'brand-yandex' => 'Яндекс',
        'cash-banknote' => 'Банкнота',
        'clipboard-list' => 'Список в буфере обмена',
        'device-mobile' => 'Мобильный телефон',
        'file-check' => 'Проверенный файл',
        'file-description' => 'Файл с описанием',
        'file-time' => 'Файл со временем',
        'lifebuoy' => 'Спасательный круг',
        'map-pin' => 'Метка на карте',
        'route' => 'Маршрут',
        'truck-delivery' => 'Доставка грузовиком',
        'user-circle' => 'Пользователь в круге',
    ];

    /** @var array<string, string> */
    private const SEARCH_ALIASES = [
        'adjustments' => 'настройки параметры фильтры ползунки',
        'bell' => 'уведомление оповещение',
        'browser' => 'браузер сайт интернет окно',
        'brand-github' => 'гитхаб гитхуб',
        'brand-telegram' => 'телеграм телега',
        'brand-vk' => 'вконтакте вк',
        'brand-yandex' => 'яндекс',
        'calendar' => 'дата событие расписание',
        'calendar-exclamation' => 'дата событие дедлайн срок предупреждение',
        'cash-banknote' => 'деньги оплата финансы рубли купюра',
        'chart-bar' => 'аналитика статистика график диаграмма отчет',
        'check' => 'галочка готово подтверждение',
        'circle-check' => 'галочка готово подтверждение',
        'clipboard-list' => 'заявка заявки список задачи буфер обмена',
        'cloud' => 'облако хранилище',
        'device-mobile' => 'телефон смартфон мобильный',
        'file' => 'файл документ',
        'file-check' => 'файл документ галочка проверка',
        'file-description' => 'файл документ описание',
        'file-plus' => 'файл документ добавить новый',
        'files' => 'файлы документы',
        'lifebuoy' => 'помощь поддержка спасательный круг',
        'mail' => 'почта письмо email электронная почта',
        'map-pin' => 'карта адрес геолокация точка метка',
        'phone' => 'телефон звонок трубка',
        'route' => 'маршрут путь направление дорога',
        'server' => 'сервер база данные',
        'settings' => 'настройки параметры шестеренка',
        'shield-check' => 'безопасность защита проверка щит',
        'truck' => 'грузовик транспорт машина фура перевозка',
        'truck-delivery' => 'грузовик транспорт машина фура перевозка доставка',
        'user' => 'пользователь человек сотрудник водитель',
        'user-circle' => 'пользователь человек сотрудник профиль аккаунт аватар',
        'user-plus' => 'пользователь сотрудник добавить пригласить',
        'users' => 'пользователи люди сотрудники команда',
    ];

    public static function title(string $name): string
    {
        $title = self::TITLE_OVERRIDES[$name]
            ?? self::translations()[$name]
            ?? str_replace('-', ' ', $name);

        return mb_strtoupper(mb_substr($title, 0, 1)).mb_substr($title, 1);
    }

    public static function searchText(string $name): string
    {
        return self::normalize(
            self::title($name).' '.(self::translations()[$name] ?? '').' '.(self::SEARCH_ALIASES[$name] ?? ''),
        );
    }

    public static function normalize(string $value): string
    {
        return str_replace('ё', 'е', mb_strtolower(trim($value)));
    }

    /** @return array<string, string> */
    private static function translations(): array
    {
        if (self::$translations !== null) {
            return self::$translations;
        }

        $path = resource_path('data/tabler-icons-ru.json');
        $contents = is_file($path) ? file_get_contents($path) : false;
        $decoded = is_string($contents) ? json_decode($contents, true) : null;

        return self::$translations = is_array($decoded) ? $decoded : [];
    }
}
