<?php
require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$view = 'components.landing.yandex-reviews';
$html = view($view, ['extra' => []])->render();
if (! str_contains($html, 'rating-badge/127614696617?type=rating')) throw new RuntimeException('Default widget missing');
foreach ([['yandex_reviews_enabled'=>false], ['yandex_reviews_code'=>''], ['yandex_reviews_code'=>null], ['yandex_reviews_code'=>'https://evil.test/']] as $extra) {
    if (str_contains(view($view, ['extra'=>$extra])->render(), '<iframe')) throw new RuntimeException('Disabled/invalid widget visible');
}
$custom = view($view, ['extra'=>['yandex_reviews_title'=>'<script>alert(1)</script>', 'yandex_reviews_code'=>'<iframe onload="alert(1)" src="https://yandex.ru/sprav/widget/rating-badge/123?type=rating"></iframe>']])->render();
if (str_contains($custom, '<script>') || str_contains($custom, 'onload=') || !str_contains($custom, '/profile/123?intent=reviews')) throw new RuntimeException('Unsafe output');
$css = '';
foreach (['base/root.css','base/reset.css','site-shared.css','landing.css'] as $f) $css .= file_get_contents(resource_path('css/'.$f));
$font = str_replace('/fonts/', '../../public/fonts/', file_get_contents(resource_path('css/base/fonts.css')));
file_put_contents(__DIR__.'/yandex-reviews-preview.html', '<!doctype html><html lang="ru"><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Проверка блока Яндекса</title><style>'.$font.$css.'body{padding:32px 0;background:white}.faq-section{padding:24px 0}</style><section class="faq-section"><div class="faq-box"><header class="section-head section-head--center"><h2>Вопрос-ответ</h2></header><div class="faq-list"><details class="faq-item"><summary class="faq-item__question">Как начать работу в логистРу?<span>+</span></summary></details><details class="faq-item"><summary class="faq-item__question">Можно ли перенести данные из другой системы?<span>+</span></summary></details></div></div>'.$html.'</section></html>');
echo "Blade rendering: default, disabled, blank, invalid and escaped content passed.\n";
