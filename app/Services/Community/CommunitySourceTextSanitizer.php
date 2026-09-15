<?php

namespace App\Services\Community;

final class CommunitySourceTextSanitizer
{
    public function sanitize(string $text): string
    {
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $patterns = [
            '/\b[\w.%+\-]+@[\w.\-]+\.[A-Z]{2,}\b/iu' => '[email]',
            '/(?<!\d)(?:\+?7|8)[\s()\-]*\d{3}[\s()\-]*\d{3}[\s\-]*\d{2}[\s\-]*\d{2}(?!\d)/u' => '[телефон]',
            '#https?://\S+#iu' => '[ссылка]',
            '/\B@[A-Za-zА-Яа-яЁё0-9_]{3,}/u' => '[пользователь]',
            '/\b[АВЕКМНОРСТУХABEKMHOPCTYX]\d{3}[АВЕКМНОРСТУХABEKMHOPCTYX]{2}\s?\d{2,3}\b/iu' => '[госномер]',
        ];

        return trim((string) preg_replace(array_keys($patterns), array_values($patterns), $text));
    }
}
