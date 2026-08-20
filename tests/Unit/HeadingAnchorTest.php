<?php

namespace Tests\Unit;

use App\Models\BlogPost;
use App\Models\CmsPage;
use App\Support\RichContent\HeadingAnchorExtension;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class HeadingAnchorTest extends TestCase
{
    #[DataProvider('richContentModels')]
    public function test_heading_anchor_is_rendered_for_rich_content(string $modelClass): void
    {
        $body = json_encode([
            'type' => 'doc',
            'content' => [
                [
                    'type' => 'heading',
                    'attrs' => [
                        'level' => 2,
                        'id' => 'delivery-terms',
                    ],
                    'content' => [[
                        'type' => 'text',
                        'text' => 'Условия доставки',
                    ]],
                ],
                [
                    'type' => 'paragraph',
                    'attrs' => [
                        'id' => 'anchor-in-plain-text',
                    ],
                    'content' => [[
                        'type' => 'text',
                        'marks' => [[
                            'type' => 'link',
                            'attrs' => [
                                'href' => '#delivery-terms',
                                'target' => null,
                            ],
                        ]],
                        'text' => 'Перейти к условиям',
                    ]],
                ],
            ],
        ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        $model = new $modelClass(['body' => $body]);

        $this->assertStringContainsString(
            '<h2 id="delivery-terms">Условия доставки</h2>',
            $model->renderBody(),
        );
        $this->assertStringContainsString(
            '<p id="anchor-in-plain-text"><a href="#delivery-terms">Перейти к условиям</a></p>',
            $model->renderBody(),
        );
    }

    public function test_heading_anchor_is_normalized(): void
    {
        $this->assertSame('usloviia-dostavki', HeadingAnchorExtension::normalize('#Условия доставки'));
        $this->assertNull(HeadingAnchorExtension::normalize('###'));
    }

    /**
     * @return array<string, array{class-string<BlogPost|CmsPage>}>
     */
    public static function richContentModels(): array
    {
        return [
            'blog post' => [BlogPost::class],
            'CMS page' => [CmsPage::class],
        ];
    }
}
