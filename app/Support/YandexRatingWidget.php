<?php

namespace App\Support;

final class YandexRatingWidget
{
    public const DEFAULT_CODE = '<iframe src="https://yandex.ru/sprav/widget/rating-badge/127614696617?type=rating" width="150" height="50" frameborder="0"></iframe>';

    public const DEFAULT_TITLE = 'Отзывы о логистРу';

    public const DEFAULT_TEXT = 'Уже пользуетесь платформой? Поделитесь опытом на Яндексе — ваше мнение поможет другим сделать выбор.';

    /** Extract the trusted URL only; pasted HTML is never rendered. */
    public static function organizationId(?string $code): ?string
    {
        if ($code === null || trim($code) === '') {
            return null;
        }
        $source = trim($code);
        if (str_starts_with($source, '<')) {
            $document = new \DOMDocument;
            $previous = libxml_use_internal_errors(true);
            try {
                $document->loadHTML($source, LIBXML_NONET);
                $frames = $document->getElementsByTagName('iframe');
                if ($frames->length !== 1) {
                    return null;
                }
                $source = $frames->item(0)->getAttribute('src');
            } finally {
                libxml_clear_errors();
                libxml_use_internal_errors($previous);
            }
        }
        $url = parse_url($source);
        if (! is_array($url)
            || ($url['scheme'] ?? '') !== 'https'
            || ($url['host'] ?? '') !== 'yandex.ru'
            || isset($url['user']) || isset($url['pass']) || isset($url['port'])
            || ! preg_match('~^/sprav/widget/rating-badge/([0-9]{1,20})/?$~D', $url['path'] ?? '', $matches)) {
            return null;
        }

        return $matches[1];
    }
}
