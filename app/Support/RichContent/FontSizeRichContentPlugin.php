<?php

namespace App\Support\RichContent;

use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor\Plugins\Contracts\RichContentPlugin;
use Filament\Forms\Components\RichEditor\RichEditorTool;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Icons\Heroicon;
use Tiptap\Core\Extension;

class FontSizeRichContentPlugin implements RichContentPlugin
{
    /**
     * @var array<int, int>
     */
    public const SIZES = [12, 14, 16, 18, 20, 24, 28, 32];

    public static function make(): static
    {
        return app(static::class);
    }

    /**
     * @return array<Extension>
     */
    public function getTipTapPhpExtensions(): array
    {
        return [app(FontSizeExtension::class)];
    }

    /**
     * @return array<string>
     */
    public function getTipTapJsExtensions(): array
    {
        return [FilamentAsset::getScriptSrc('rich-content-plugins/font-size')];
    }

    /**
     * @return array<RichEditorTool>
     */
    public function getEditorTools(): array
    {
        return [
            RichEditorTool::make('fontSizeDefault')
                ->label('Обычный')
                ->jsHandler('$getEditor()?.chain().focus().unsetFontSize().run()')
                ->activeJsExpression('! $getEditor()?.isActive(\'fontSize\')')
                ->icon(Heroicon::OutlinedArrowsUpDown),
            ...array_map(
                fn (int $size): RichEditorTool => RichEditorTool::make("fontSize{$size}")
                    ->label("{$size} px")
                    ->jsHandler("\$getEditor()?.chain().focus().setFontSize({ size: '{$size}' }).run()")
                    ->activeKey('fontSize')
                    ->activeOptions(['data-font-size' => (string) $size])
                    ->icon(Heroicon::OutlinedArrowsUpDown),
                self::SIZES,
            ),
        ];
    }

    /**
     * @return array<Action>
     */
    public function getEditorActions(): array
    {
        return [];
    }
}
