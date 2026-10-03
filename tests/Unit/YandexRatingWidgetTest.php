<?php

namespace Tests\Unit;

use App\Support\YandexRatingWidget;
use PHPUnit\Framework\TestCase;

class YandexRatingWidgetTest extends TestCase
{
    public function test_extracts_company_from_embed_and_url(): void
    {
        self::assertSame('127614696617', YandexRatingWidget::organizationId(YandexRatingWidget::DEFAULT_CODE));
        self::assertSame('123', YandexRatingWidget::organizationId('https://yandex.ru/sprav/widget/rating-badge/123?type=rating'));
        self::assertSame('456', YandexRatingWidget::organizationId("<iframe height='50' src='https://yandex.ru/sprav/widget/rating-badge/456?type=rating'></iframe>"));
    }

    public function test_rejects_untrusted_and_ambiguous_sources(): void
    {
        foreach ([null, '', '<script>alert(1)</script>',
            'https://yandex.ru.evil.test/sprav/widget/rating-badge/123',
            'https://yandex.ru@evil.test/sprav/widget/rating-badge/123',
            'http://yandex.ru/sprav/widget/rating-badge/123',
            'https://yandex.ru:8443/sprav/widget/rating-badge/123',
            'https://yandex.ru/sprav/widget/rating-badge/abc',
            '<iframe src="javascript:alert(1)"></iframe>',
            YandexRatingWidget::DEFAULT_CODE.YandexRatingWidget::DEFAULT_CODE,
        ] as $code) {
            self::assertNull(YandexRatingWidget::organizationId($code));
        }
    }
}
