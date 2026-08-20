<?php

namespace App\Support\RichContent;

use Illuminate\Support\Str;
use Tiptap\Core\Extension;

class HeadingAnchorExtension extends Extension
{
    public static $name = 'headingAnchor';

    public function addGlobalAttributes(): array
    {
        return [
            [
                'types' => ['heading', 'paragraph'],
                'attributes' => [
                    'id' => [
                        'default' => null,
                        'parseHTML' => fn ($DOMNode): ?string => self::normalize($DOMNode->getAttribute('id')),
                        'renderHTML' => function ($attributes): ?array {
                            $anchor = self::normalize($this->attributeValue($attributes));

                            return $anchor === null ? null : ['id' => $anchor];
                        },
                    ],
                ],
            ],
        ];
    }

    public static function normalize(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $value = ltrim(trim((string) $value), '#');
        $value = Str::slug($value);

        return $value === '' ? null : Str::limit($value, 100, '');
    }

    private function attributeValue(mixed $attributes): mixed
    {
        if (is_array($attributes)) {
            return $attributes['id'] ?? null;
        }

        if (is_object($attributes)) {
            return $attributes->id ?? null;
        }

        return null;
    }
}
