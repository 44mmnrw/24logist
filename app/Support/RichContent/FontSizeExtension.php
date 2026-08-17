<?php

namespace App\Support\RichContent;

use Tiptap\Core\Mark;

class FontSizeExtension extends Mark
{
    public static $name = 'fontSize';

    /**
     * @var array<int, string>
     */
    private const ALLOWED_SIZES = ['12', '14', '16', '18', '20', '24', '28', '32'];

    /**
     * @return array<int, array<string, mixed>>
     */
    public function parseHTML(): array
    {
        return [
            [
                'tag' => 'span',
                'getAttrs' => fn ($DOMNode): bool => in_array('font-size', explode(' ', (string) $DOMNode->getAttribute('class')), true),
            ],
        ];
    }

    /**
     * @return array<string, array<mixed>>
     */
    public function addAttributes(): array
    {
        return [
            'data-font-size' => [
                'parseHTML' => fn ($DOMNode): ?string => $this->normalizeSize($DOMNode->getAttribute('data-font-size')),
                'renderHTML' => fn ($attributes): array => [
                    'data-font-size' => $this->normalizeSize($this->attributeValue($attributes)),
                ],
            ],
        ];
    }

    /**
     * @param  object  $mark
     * @param  array<string, mixed>  $HTMLAttributes
     * @return array<mixed>
     */
    public function renderHTML($mark, $HTMLAttributes = []): array
    {
        $existingClass = isset($HTMLAttributes['class']) ? (string) $HTMLAttributes['class'] : '';
        $size = $this->normalizeSize($HTMLAttributes['data-font-size'] ?? null)
            ?? $this->normalizeSize($this->attributeValue($mark->attrs ?? null));
        $HTMLAttributes['class'] = trim(implode(' ', array_filter([
            'font-size',
            $size !== null ? "font-size-{$size}" : null,
            $existingClass,
        ])));

        if ($size !== null) {
            $HTMLAttributes['data-font-size'] = $size;
        } else {
            unset($HTMLAttributes['data-font-size']);
        }

        return ['span', $HTMLAttributes, 0];
    }

    private function attributeValue(mixed $attributes): mixed
    {
        if (is_array($attributes)) {
            return $attributes['data-font-size'] ?? null;
        }

        if (is_object($attributes)) {
            return $attributes->{'data-font-size'} ?? ($attributes->dataFontSize ?? null);
        }

        return null;
    }

    private function normalizeSize(mixed $size): ?string
    {
        $size = is_scalar($size) ? (string) $size : null;

        return in_array($size, self::ALLOWED_SIZES, true) ? $size : null;
    }
}
