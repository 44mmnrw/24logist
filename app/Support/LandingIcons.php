<?php

namespace App\Support;

final class LandingIcons
{
    private const TABLER_VERSION = 'v3.46.0';

    private const TABLER_PREFIX = 'tabler:';

    private const TABLER_FILLED_PREFIX = 'tabler-filled:';

    /** @var array<string, string> */
    private const LEGACY_TABLER_MAP = [
        'brand-logo' => 'truck-delivery',
        'brand-mark' => 'truck-delivery',
        'telegram' => 'brand-telegram',
        'badge-star' => 'star',
        'x' => 'x',
        'eye' => 'eye',
        'eye-off' => 'eye-off',
        'check' => 'check',
        'check-blue' => 'check',
        'check-circle' => 'circle-check',
        'doc-check-circle' => 'file-check',
        'arrow-right' => 'arrow-right',
        'chevron-down' => 'chevron-down',
        'info-circle' => 'info-circle',
        'user-driver' => 'user',
        'manager-avatar' => 'user-circle',
        'truck' => 'truck',
        'document-fast' => 'file-time',
        'document-signed' => 'file-check',
        'epd-platform' => 'file-description',
        'documents' => 'files',
        'chart-bar' => 'chart-bar',
        'lifebuoy' => 'lifebuoy',
        'shield-check' => 'shield-check',
        'calendar-alert' => 'calendar-exclamation',
        'clipboard-list' => 'clipboard-list',
        'clock' => 'clock',
        'server' => 'server',
        'route' => 'route',
        'calculator' => 'calculator',
        'folder-archive' => 'archive',
        'bell' => 'bell',
        'banknotes' => 'cash-banknote',
        'users-card' => 'address-book',
        'rotes' => 'route',
        'sliders' => 'adjustments',
        'smartphone' => 'device-mobile',
        'browser' => 'browser',
        'menu-dots' => 'dots',
        'mail' => 'mail',
        'phone' => 'phone',
        'home' => 'home',
        'tech-support' => 'headset',
        'additional-seat' => 'user-plus',
        'additional-epd' => 'file-plus',
        'additional-cloud' => 'cloud-plus',
    ];

    /** @var list<string> */
    private const DEFAULT_TABLER_ICONS = [
        'check',
        'circle-check',
        'info-circle',
        'arrow-right',
        'chevron-down',
        'truck',
        'route',
        'map-pin',
        'user',
        'users',
        'user-plus',
        'phone',
        'mail',
        'bell',
        'clock',
        'calendar',
        'calendar-exclamation',
        'clipboard-list',
        'file',
        'file-check',
        'file-plus',
        'files',
        'folder',
        'archive',
        'chart-bar',
        'calculator',
        'cash-banknote',
        'shield-check',
        'lifebuoy',
        'headset',
        'settings',
        'adjustments',
        'device-mobile',
        'browser',
        'cloud',
        'cloud-plus',
        'server',
        'link',
        'star',
        'heart',
    ];

    /** @var array<string, true>|null */
    private static ?array $tablerIcons = null;

    /** @var array<string, true>|null */
    private static ?array $tablerFilledIcons = null;

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
        'telegram' => 'Telegram',
        'badge-star' => 'Бейдж (звезда)',
        'x' => 'Закрыть',
        'eye' => 'Показать пароль',
        'eye-off' => 'Скрыть пароль',
        'check' => 'Галочка',
        'check-blue' => 'Галочка (синяя)',
        'check-circle' => 'Галочка в круге',
        'doc-check-circle' => 'Точка маршрута доставки',
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

        if (str_starts_with($value, self::TABLER_FILLED_PREFIX)) {
            $name = substr($value, strlen(self::TABLER_FILLED_PREFIX));

            return isset(self::tablerIconMap(true)[$name])
                ? self::TABLER_FILLED_PREFIX.$name
                : null;
        }

        if (str_starts_with($value, self::TABLER_PREFIX)) {
            $name = substr($value, strlen(self::TABLER_PREFIX));

            return isset(self::tablerIconMap(false)[$name])
                ? self::TABLER_PREFIX.$name
                : null;
        }

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

        if (str_starts_with($name, self::TABLER_PREFIX) || str_starts_with($name, self::TABLER_FILLED_PREFIX)) {
            return $name;
        }

        return str_starts_with($name, 'icon:') ? $name : 'icon:'.$name;
    }

    public static function normalize(?string $value): ?string
    {
        $resolved = self::toTabler($value);

        return $resolved !== null ? self::toStorage($resolved) : null;
    }

    public static function toTabler(?string $value): ?string
    {
        $resolved = self::resolve($value);

        if ($resolved === null || self::isTabler($resolved)) {
            return $resolved;
        }

        $tablerName = self::LEGACY_TABLER_MAP[$resolved] ?? null;

        if ($tablerName === null) {
            return null;
        }

        return self::TABLER_PREFIX.$tablerName;
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
        if (str_starts_with($name, self::TABLER_FILLED_PREFIX)) {
            $icon = substr($name, strlen(self::TABLER_FILLED_PREFIX));

            return asset('icons/tabler/'.self::TABLER_VERSION.'/tabler-sprite-filled.svg').'#tabler-filled-'.$icon;
        }

        if (str_starts_with($name, self::TABLER_PREFIX)) {
            $icon = substr($name, strlen(self::TABLER_PREFIX));

            return asset('icons/tabler/'.self::TABLER_VERSION.'/tabler-sprite.svg').'#tabler-'.$icon;
        }

        return '#icon-'.$name;
    }

    public static function previewHref(string $name): string
    {
        if (self::isTabler($name)) {
            return self::symbolHref($name);
        }

        return asset('images/icons/sprite.svg').'#icon-'.$name;
    }

    public static function viewBox(string $name): string
    {
        if (self::isTabler($name)) {
            return '0 0 24 24';
        }

        return match ($name) {
            'check-blue', 'document-fast', 'chart-bar', 'lifebuoy', 'shield-check', 'document-signed', 'truck', 'user-driver', 'calendar-alert', 'calculator', 'banknotes', 'rotes', 'folder-archive', 'bell', 'users-card' => '0 0 20 20',
            'doc-check-circle' => '0 0 16 16',
            'additional-seat', 'additional-epd', 'additional-cloud' => '0 0 18 18',
            'manager-avatar' => '0 0 32 32',
            'telegram' => '0 0 240.1 240.1',
            default => '0 0 24 24',
        };
    }

    public static function spritePath(): string
    {
        return asset('images/icons/sprite.svg');
    }

    public static function isTabler(string $name): bool
    {
        return str_starts_with($name, self::TABLER_PREFIX)
            || str_starts_with($name, self::TABLER_FILLED_PREFIX);
    }

    /** @return array<string, string> */
    public static function initialOptions(): array
    {
        $options = [];

        foreach (self::DEFAULT_TABLER_ICONS as $name) {
            $value = self::TABLER_PREFIX.$name;

            if (isset(self::tablerIconMap(false)[$name])) {
                $options[$value] = self::optionLabel($value) ?? $name;
            }
        }

        return $options;
    }

    /** @return array<string, string> */
    public static function searchOptions(string $search, int $limit = 60): array
    {
        $search = TablerIconTranslations::normalize($search);

        if ($search === '') {
            return self::initialOptions();
        }

        $matches = [];

        foreach (self::tablerIconMap(false) as $name => $_) {
            $score = self::searchScore(
                $search,
                $name.' '.TablerIconTranslations::searchText($name).' tabler outline контурная',
            );

            if ($score !== null) {
                $value = self::TABLER_PREFIX.$name;
                $matches[] = [$score, $value, self::optionLabel($value) ?? $name];
            }
        }

        foreach (self::tablerIconMap(true) as $name => $_) {
            $score = self::searchScore(
                $search,
                $name.' '.TablerIconTranslations::searchText($name).' tabler filled заливка',
            );

            if ($score !== null) {
                $value = self::TABLER_FILLED_PREFIX.$name;
                $matches[] = [$score, $value, self::optionLabel($value) ?? $name];
            }
        }

        usort($matches, static fn (array $left, array $right): int => [$left[0], $left[1]] <=> [$right[0], $right[1]]);

        $options = [];

        foreach (array_slice($matches, 0, $limit) as [, $value, $label]) {
            $options[$value] = $label;
        }

        return $options;
    }

    public static function optionLabel(?string $value): ?string
    {
        $name = self::toTabler($value);

        if ($name === null) {
            return null;
        }

        if (str_starts_with($name, self::TABLER_FILLED_PREFIX)) {
            $slug = substr($name, strlen(self::TABLER_FILLED_PREFIX));
            $kind = 'Заливка';
        } elseif (str_starts_with($name, self::TABLER_PREFIX)) {
            $slug = substr($name, strlen(self::TABLER_PREFIX));
            $kind = 'Контур';
        } else {
            return null;
        }

        $href = e(self::previewHref($name));
        $viewBox = e(self::viewBox($name));
        $title = e(TablerIconTranslations::title($slug));
        $slug = e($slug);
        $kind = e($kind);

        return '<span style="display:flex;align-items:center;gap:.625rem;min-width:0">'
            .'<svg viewBox="'.$viewBox.'" aria-hidden="true" style="width:1.35rem;height:1.35rem;flex:none;color:currentColor">'
            .'<use href="'.$href.'"></use></svg>'
            .'<span style="display:flex;min-width:0;flex-direction:column;line-height:1.2">'
            .'<span style="overflow:hidden;text-overflow:ellipsis">'.$title.'</span>'
            .'<span style="overflow:hidden;text-overflow:ellipsis;opacity:.55;font-size:.72rem">'.$slug.'</span>'
            .'</span>'
            .'<span style="margin-left:auto;opacity:.6;font-size:.75rem;white-space:nowrap">'.$kind.'</span>'
            .'</span>';
    }

    /** @return array<string, true> */
    private static function tablerIconMap(bool $filled): array
    {
        if ($filled && self::$tablerFilledIcons !== null) {
            return self::$tablerFilledIcons;
        }

        if (! $filled && self::$tablerIcons !== null) {
            return self::$tablerIcons;
        }

        $file = public_path('icons/tabler/'.self::TABLER_VERSION.'/tabler-sprite'.($filled ? '-filled' : '').'.svg');
        $contents = is_file($file) ? file_get_contents($file) : false;
        $prefix = $filled ? 'tabler-filled-' : 'tabler-';
        $names = [];

        if (is_string($contents)) {
            preg_match_all('/<symbol id="'.preg_quote($prefix, '/').'([a-z0-9-]+)"/', $contents, $matches);

            foreach ($matches[1] ?? [] as $name) {
                $names[$name] = true;
            }
        }

        if ($filled) {
            return self::$tablerFilledIcons = $names;
        }

        return self::$tablerIcons = $names;
    }

    private static function searchScore(string $needle, string $haystack): ?int
    {
        if ($needle === $haystack) {
            return 0;
        }

        if (str_starts_with($haystack, $needle)) {
            return 1;
        }

        if (preg_match('/(?:^|[\s:-])'.preg_quote($needle, '/').'/', $haystack) === 1) {
            return 2;
        }

        return str_contains($haystack, $needle) ? 3 : null;
    }
}
