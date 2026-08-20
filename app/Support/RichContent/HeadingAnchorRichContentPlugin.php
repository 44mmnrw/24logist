<?php

namespace App\Support\RichContent;

use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\RichEditor\EditorCommand;
use Filament\Forms\Components\RichEditor\Plugins\Contracts\HasToolbarButtons;
use Filament\Forms\Components\RichEditor\Plugins\Contracts\RichContentPlugin;
use Filament\Forms\Components\RichEditor\RichEditorTool;
use Filament\Forms\Components\TextInput;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Icons\Heroicon;
use Tiptap\Core\Extension;

class HeadingAnchorRichContentPlugin implements HasToolbarButtons, RichContentPlugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    /**
     * @return array<Extension>
     */
    public function getTipTapPhpExtensions(): array
    {
        return [app(HeadingAnchorExtension::class)];
    }

    /**
     * @return array<string>
     */
    public function getTipTapJsExtensions(): array
    {
        return [FilamentAsset::getScriptSrc('rich-content-plugins/heading-anchor')];
    }

    /**
     * @return array<RichEditorTool>
     */
    public function getEditorTools(): array
    {
        return [
            RichEditorTool::make('headingAnchor')
                ->label('Якорь блока')
                ->action(arguments: "{ nodeType: \$getEditor().isActive('heading') ? 'heading' : 'paragraph', anchor: \$getEditor().isActive('heading') ? (\$getEditor().getAttributes('heading')?.id ?? null) : (\$getEditor().getAttributes('paragraph')?.id ?? null) }")
                ->activeJsExpression("\$getEditor()?.isActive('heading') || \$getEditor()?.isActive('paragraph')")
                ->activeStyling(false)
                ->disabledWhenNotActive()
                ->icon(Heroicon::OutlinedHashtag),
        ];
    }

    /**
     * @return array<Action>
     */
    public function getEditorActions(): array
    {
        return [
            Action::make('headingAnchor')
                ->label('Якорь блока')
                ->modalHeading('Якорь блока')
                ->fillForm(fn (array $arguments): array => [
                    'anchor' => $arguments['anchor'] ?? null,
                ])
                ->schema([
                    TextInput::make('anchor')
                        ->label('Якорь')
                        ->prefix('#')
                        ->maxLength(100)
                        ->placeholder('usloviya-dostavki')
                        ->helperText('Пробелы и кириллица автоматически преобразуются. Оставьте поле пустым, чтобы удалить якорь.'),
                ])
                ->action(function (array $arguments, array $data, RichEditor $component): void {
                    $anchor = HeadingAnchorExtension::normalize($data['anchor'] ?? null);
                    $nodeType = in_array($arguments['nodeType'] ?? null, ['heading', 'paragraph'], true)
                        ? $arguments['nodeType']
                        : 'paragraph';
                    $command = $anchor === null
                        ? EditorCommand::make('resetAttributes', arguments: [$nodeType, 'id'])
                        : EditorCommand::make('updateAttributes', arguments: [$nodeType, ['id' => $anchor]]);

                    $component->runCommands(
                        [$command],
                        editorSelection: $arguments['editorSelection'],
                    );
                }),
        ];
    }

    public function getEnabledToolbarButtons(): array
    {
        return [['headingAnchor']];
    }

    public function getDisabledToolbarButtons(): array
    {
        return [];
    }
}
