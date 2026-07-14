<?php

namespace App\Support;

final class LandingIcons
{
    /** @var array<string, string> */
    private const ICON_ALIASES = [
        'map-pin' => 'rotes',
        'check-green' => 'check-blue',
    ];

    /** @var array<string, string> */
    private const LEGACY_URL_MAP = [
        '4a52bb50-381b-4e51-9404-3a9d2ca17c85' => 'check-blue',
        '2fcbc75e-2d93-44aa-8d9e-1e692f633f06' => 'check-circle',
        '066376e7-72a9-4e53-8d21-623a41ded2ad' => 'check',
        '41728557-50f0-4280-8e1d-ba9e0a44d6b9' => 'brand-logo',
        '3ba2ad32-a3b6-4e2e-ab03-45c8c9c20b51' => 'badge-star',
        '3026e7c0-5f63-423c-937d-98a36923b967' => 'info-circle',
        '6189b3eb-cfd2-4803-8c46-9d472f8a965b' => 'arrow-right',
        '70c3cc5d-4b3c-49d3-8d8b-3444d9af22f6' => 'user-driver',
        'f3ba819d-bdb6-43a9-ba22-b6a4ea399355' => 'truck',
        '26c44286-3e36-4da5-bba3-5e72ad67a292' => 'document-fast',
        'cb0db66b-aaa5-454b-94cc-264928cdbea3' => 'chart-bar',
        'a902eceb-516d-4760-8e8d-5e0a372e393f' => 'lifebuoy',
        '6fc41fe8-b1a9-4945-a0ba-3d64268304c0' => 'shield-check',
        '0409e4e6-49eb-4a25-ab00-1280daf36607' => 'document-signed',
        'bcfc6842-d058-4d9f-add1-269824031f7c' => 'calendar-alert',
        '5c8d6f6c-adfa-4665-a03e-7320ea71a68d' => 'clipboard-list',
        'd0dd37fd-f3f5-4b52-90c5-2157f2fa2d3c' => 'clock',
        '3d21c7f0-fb6a-4876-b166-8b5058eb2cc6' => 'documents',
        '3391dae6-d831-49b0-9752-1688023edfdf' => 'server',
        '4b55c2af-b2a3-4ebc-b7de-8d1eb7ae867e' => 'route',
        '696ef874-3a40-422b-b722-e687cfa3b0b4' => 'calculator',
        '5e6d8aff-559b-4ecc-907c-739539249387' => 'folder-archive',
        '4ee9f7ad-dc08-4fe1-896a-f6247a3f7827' => 'bell',
        '5f972148-81f6-45af-827d-8fd34e17af71' => 'banknotes',
        'f3747d7f-f26c-4467-817f-b90c087479a9' => 'users-card',
        'b13d37dd-b731-40df-b92c-f6c92715dcd8' => 'rotes',
        '9f5dbdda-a27d-4da8-8d5e-559f2aa8309b' => 'sliders',
        '48bd66e6-ed63-46a3-8ff6-e7f285997bd7' => 'check-blue',
        '2afefcd1-5854-4df1-8caf-77ef412052ce' => 'smartphone',
        '3f6fdc5d-d097-494a-b9a3-e41b891b09c5' => 'browser',
        'be291b50-e608-43c5-9166-d4ef099cdfb9' => 'brand-mark',
        'b44c3691-f0eb-464d-9291-e38929f433c9' => 'menu-dots',
        'b8be60e0-85be-4f89-8278-4ad5d360f65f' => 'arrow-right',
        '587bcc5c-7bb8-450e-a5c1-2b44cff02314' => 'chevron-down',
        'febf5d9b-3387-4830-b9a4-baa135fd6ee7' => 'brand-logo',
        '5ba631bc-e285-4b7c-96c0-8e118558553a' => 'mail',
        '09d3c9dd-dfd5-43f2-9788-4c07888aafaa' => 'phone',
    ];

    /** @var array<string, string> */
    public const OPTIONS = [
        'brand-logo' => 'Логотип',
        'brand-mark' => 'Марк логотипа',
        'badge-star' => 'Бейдж (звезда)',
        'check' => 'Галочка',
        'check-blue' => 'Галочка (синяя)',
        'check-circle' => 'Галочка в круге',
        'arrow-right' => 'Стрелка вправо',
        'chevron-down' => 'Шеврон вниз',
        'info-circle' => 'Информация',
        'user-driver' => 'Водитель',
        'manager-avatar' => 'Аватар менеджера',
        'truck' => 'Транспорт',
        'document-fast' => 'Быстрый документ',
        'document-signed' => 'Подписанный документ',
        'epd-platform' => 'Платформа ЭПД',
        'documents' => 'Документы',
        'chart-bar' => 'Аналитика',
        'lifebuoy' => 'Поддержка',
        'shield-check' => 'Безопасность',
        'calendar-alert' => 'Дедлайн',
        'clipboard-list' => 'Заявки',
        'clock' => 'Время',
        'server' => 'Сервер',
        'route' => 'Маршрут',
        'calculator' => 'Калькулятор',
        'folder-archive' => 'Архив',
        'bell' => 'Уведомления',
        'banknotes' => 'Финансы',
        'users-card' => 'Контрагенты',
        'rotes' => 'Маршруты',
        'sliders' => 'Настройки',
        'smartphone' => 'Смартфон',
        'browser' => 'Браузер',
        'menu-dots' => 'Меню',
        'mail' => 'Email',
        'phone' => 'Телефон',
        'home' => 'Главная',
        'tech-support' => 'Техподдержка',
        'additional-seat' => 'Дополнительное рабочее место',
        'additional-epd' => 'Дополнительный пакет ЭПД',
        'additional-cloud' => 'Дополнительное место в облаке',
    ];

    public static function resolve(?string $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        $value = trim($value);

        if (str_starts_with($value, 'icon:')) {
            $name = substr($value, 5);
            $name = self::ICON_ALIASES[$name] ?? $name;

            return array_key_exists($name, self::OPTIONS) ? $name : null;
        }

        if (array_key_exists($value, self::ICON_ALIASES)) {
            $value = self::ICON_ALIASES[$value];
        }

        if (array_key_exists($value, self::OPTIONS)) {
            return $value;
        }

        if (str_contains($value, 'figma.com/api/mcp/asset/')) {
            foreach (self::LEGACY_URL_MAP as $uuid => $name) {
                if (str_contains($value, $uuid)) {
                    return $name;
                }
            }
        }

        return null;
    }

    public static function toStorage(?string $name): ?string
    {
        if (blank($name)) {
            return null;
        }

        return str_starts_with($name, 'icon:') ? $name : 'icon:'.$name;
    }

    public static function normalize(?string $value): ?string
    {
        $resolved = self::resolve($value);

        return $resolved !== null ? self::toStorage($resolved) : null;
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    public static function normalizeExtraIcons(array $extra): array
    {
        foreach ($extra as $key => $value) {
            if (! is_string($value) || ! str_ends_with($key, '_icon')) {
                continue;
            }

            $extra[$key] = self::normalize($value);
        }

        return $extra;
    }

    public static function symbolHref(string $name): string
    {
        return '#icon-'.$name;
    }

    public static function viewBox(string $name): string
    {
        return match ($name) {
            'check-blue', 'document-fast', 'chart-bar', 'lifebuoy', 'shield-check', 'document-signed', 'truck', 'user-driver', 'calendar-alert', 'calculator', 'banknotes', 'rotes', 'folder-archive', 'bell', 'users-card' => '0 0 20 20',
            'additional-seat', 'additional-epd', 'additional-cloud' => '0 0 18 18',
            'manager-avatar' => '0 0 32 32',
            default => '0 0 24 24',
        };
    }

    public static function spritePath(): string
    {
        return asset('images/icons/sprite.svg');
    }
}
