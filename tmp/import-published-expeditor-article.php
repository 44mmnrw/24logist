<?php

declare(strict_types=1);

use App\Models\BlogCategory;
use App\Models\BlogPost;
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$sourcePath = __DIR__.'/ekspeditor-2026-published.html';

if (! is_file($sourcePath)) {
    throw new RuntimeException("Published article snapshot was not found: {$sourcePath}");
}

$html = file_get_contents($sourcePath);

if ($html === false || $html === '') {
    throw new RuntimeException('Published article snapshot is empty.');
}

$dom = new DOMDocument('1.0', 'UTF-8');
$previous = libxml_use_internal_errors(true);
$dom->loadHTML('<?xml encoding="UTF-8">'.$html, LIBXML_NOERROR | LIBXML_NOWARNING);
libxml_clear_errors();
libxml_use_internal_errors($previous);

$xpath = new DOMXPath($dom);
$bodyNode = $xpath->query(
    '//*[contains(concat(" ", normalize-space(@class), " "), " blog-post-body--article ")]',
)?->item(0);

if (! $bodyNode instanceof DOMElement) {
    throw new RuntimeException('Article body was not found in the published page.');
}

$body = '';

foreach ($bodyNode->childNodes as $child) {
    $body .= $dom->saveHTML($child);
}

$body = trim($body);

$friendlyIntro = <<<'HTML'
<p>С 1 сентября 2026 года привычные документы экспедитора переходят в электронный вид. Поручение, экспедиторская и складская расписки нужно будет оформлять через оператора ИС ЭПД, а сведения из них — передавать в ГИС ЭПД.</p>
<p>Главная задача здесь не в XML и кнопках. Важно правильно указать роли участников: кто заказывает перевозку, кто принимает груз и от чьего имени подписывается каждый документ.</p>
<h1>Что такое транспортная экспедиция по закону</h1>
<p>Если проще, экспедитор организует перевозку: находит перевозчика, согласует маршрут, оформляет документы и контролирует рейс. Везти груз на собственном автомобиле он не обязан. Это следует из <a href="https://www.consultant.ru/document/cons_doc_LAW_43006/27f9ddea0cccf9a6b90bb2cb8b545d436f18157b/" target="_blank" rel="noopener noreferrer">Федерального закона № 87-ФЗ</a> и <a href="https://www.consultant.ru/document/cons_doc_LAW_9027/bf3951e6ba650293a571c5ac9176cf6b7af35874/" target="_blank" rel="noopener noreferrer">статьи 801 ГК РФ</a>.</p>
<p>Если компания сама принимает груз к перевозке и везёт его своим транспортом без отдельной экспедиционной услуги, она выступает уже как перевозчик.</p>
<p>В одном рейсе идут два связанных, но разных процесса:</p>
<ul>
<li><strong>экспедиция</strong> — отношения клиента и экспедитора;</li>
<li><strong>перевозка</strong> — отношения грузоотправителя и перевозчика.</li>
</ul>
<p>Поэтому поручение экспедитору не заменяет ЭТрН, а ЭТрН не заменяет расписку, когда она действительно нужна. Набор документов зависит от двух вещей: принимает ли экспедитор груз и от чьего имени заключён договор перевозки.</p>
<p>Ниже разберём рабочие сценарии по шагам. Материал актуален на 21 августа 2026 года.</p>
HTML;

$body = preg_replace(
    '~\A.*?(?=<h1>Как работает экспедитор с 1 сентября 2026 года</h1>)~su',
    $friendlyIntro,
    $body,
    1,
    $introReplacementCount,
);

if ($introReplacementCount !== 1) {
    throw new RuntimeException("Unexpected intro replacement count: {$introReplacementCount}");
}

$friendlyBaseScenario = <<<'HTML'
<h1>Базовый сценарий: экспедитор организует рейс, груз забирает перевозчик</h1>
<p>Это самый частый вариант. Экспедитор находит машину и ведёт рейс, но не принимает груз на хранение или во владение. На погрузке склад или поставщик передаёт груз сразу перевозчику.</p>
<p><strong>Главное правило:</strong> договор перевозки и договор хранения экспедитор заключает <strong>от имени клиента по доверенности</strong>. Подписать их от своего имени в этой модели нельзя. Это прямо следует из <a href="https://www.consultant.ru/document/cons_doc_LAW_43006/f373fcdae66c4779450a56393408ed3b76cc7dfb/">подпункта 2 пункта 4.1 статьи 4 Федерального закона № 87-ФЗ</a>.</p>
<p>Другие вспомогательные договоры экспедитор может заключать и от своего имени. Тогда клиент получает копию. Если договор заключён от имени клиента, ему передаётся оригинал.</p>
<p>В одном рейсе всё происходит так.</p>
<ol>
<li><p><strong>Клиент подключается к оператору ЭПД</strong> и получает собственный электронный ящик. Одновременно он выдаёт сотруднику экспедитора доверенность, а для электронной подписи — МЧД.</p></li>
<li><p><strong>Клиент отправляет поручение.</strong> В первом файле указывает стороны, груз, маршрут, услуги и важные инструкции.</p></li>
<li><p><strong>Экспедитор проверяет данные.</strong> Если чего-то не хватает, запрашивает уточнение. Когда всё согласовано, отправляет второй файл поручения.</p></li>
<li><p><strong>Экспедитор находит перевозчика</strong> и от имени клиента оформляет договор перевозки, заказ или заявку. В этих документах клиент остаётся стороной договора.</p></li>
<li><p><strong>До погрузки формируется первый файл ЭТрН.</strong> Клиент указывается грузоотправителем, а склад или поставщик — фактическим исполнителем погрузки. Экспедитор может подготовить и подписать файл только как представитель клиента по МЧД.</p></li>
<li><p><strong>Перевозчик и грузополучатель завершают ЭТрН.</strong> Каждый формирует свои файлы. Операторы доставляют их другим участникам, клиенту и в ГИС ЭПД.</p></li>
<li><p><strong>Экспедитор закрывает рейс:</strong> следит за статусами, сверяет расходы и оформляет свою услугу клиенту.</p></li>
</ol>
<p><strong>Экспедиторская расписка здесь не нужна:</strong> экспедитор груз не принимал. Но документы и фактическая работа должны совпадать — перевозчик действительно забирает груз напрямую у грузоотправителя.</p>
<h2>Как документы окажутся у клиента</h2>
<p>Экспедитор может работать в привычном кабинете своего оператора. Важно не то, где он вошёл в систему, а <strong>от чьего имени действует</strong>.</p>
<p>При оформлении документов нужно:</p>
<ul>
<li>выбрать режим работы за клиента;</li>
<li>указать клиента стороной договора и грузоотправителем;</li>
<li>подписать файл как представитель клиента по МЧД;</li>
<li>направить все файлы оператору клиента.</li>
</ul>
<p>Если оператор не умеет работать с представителями, экспедитор готовит черновик, а клиент подписывает и отправляет его из своего ящика.</p>
<p>В архив клиента должны попасть XML-файлы, электронные подписи, УИД, квитанции оператора и все последующие файлы участников. PDF или ZIP, присланный экспедитором, — удобная копия, но не замена электронным оригиналам.</p>
<p>ГИС ЭПД хранит сведения для государства, но не заменяет архив компании. Срок хранения, выгрузку документов и интеграцию с учётной системой лучше заранее закрепить в договоре с оператором.</p>
<p><strong>Важно:</strong> если экспедитор заключает договор перевозки от своего имени, это уже другой сценарий. Тогда нужно отдельно проверить роли в ЭТрН и нужна ли экспедиторская расписка.</p>
HTML;

$body = preg_replace(
    '~<h1>Базовый сценарий: экспедитор организует перевозку, но не принимает груз</h1>.*?(?=<h1>Сценарий: экспедитор принимает груз у клиента</h1>)~su',
    $friendlyBaseScenario,
    $body,
    1,
    $baseScenarioReplacementCount,
);

if ($baseScenarioReplacementCount !== 1) {
    throw new RuntimeException("Unexpected base scenario replacement count: {$baseScenarioReplacementCount}");
}

$infographic = <<<'HTML'
<p><img src="/images/blog/ekspeditor-2026/basic-scenario.svg?v=6" alt="Базовый сценарий: экспедитор действует от имени клиента по МЧД, не принимает груз, а электронные оригиналы поступают в архив клиента"></p><p style="text-align: center;"><em>Юридическая модель, движение груза и доставка электронных оригиналов клиенту</em></p>
HTML;

$marker = '<p>В одном рейсе всё происходит так.</p>';

if (! str_contains($body, 'basic-scenario.svg')) {
    if (! str_contains($body, $marker)) {
        throw new RuntimeException('Infographic insertion point was not found in the article body.');
    }

    $body = str_replace($marker, $infographic.$marker, $body, $count);

    if ($count !== 1) {
        throw new RuntimeException("Unexpected infographic insertion count: {$count}");
    }
}

$meta = static function (string $attribute, string $value) use ($xpath): ?string {
    $node = $xpath->query(sprintf('//meta[@%s="%s"]', $attribute, $value))?->item(0);
    $content = $node instanceof DOMElement ? trim($node->getAttribute('content')) : '';

    return $content !== '' ? $content : null;
};

$titleNode = $xpath->query('//main//*[self::h1][1]')?->item(0);
$title = $titleNode instanceof DOMElement
    ? trim($titleNode->textContent)
    : 'Как будет работать экспедиторская компания с 1 сентября 2026 года: документы и сценарии';

$description = $meta('name', 'description')
    ?? 'Как будет работать экспедиторская компания с 1 сентября 2026 года: поручение, ЭТрН, роли участников, архив электронных оригиналов и базовый сценарий без принятия груза.';

$category = BlogCategory::query()->firstOrCreate(
    ['slug' => 'prakticeskoe-rukovodstvo'],
    [
        'name' => 'Практическое руководство',
        'description' => 'Пошаговые материалы для участников грузоперевозок',
        'is_active' => true,
        'sort_order' => 20,
    ],
);

$backupPath = null;
$existingPost = BlogPost::query()
    ->where('slug', 'kak-budet-rabotat-ekspeditorskaya-kompaniya-s-1-sentyabrya-2026-goda')
    ->first();

if ($existingPost !== null) {
    $backupDirectory = storage_path('app/deployment-backups');

    if (! is_dir($backupDirectory) && ! mkdir($backupDirectory, 0775, true) && ! is_dir($backupDirectory)) {
        throw new RuntimeException("Unable to create backup directory: {$backupDirectory}");
    }

    $backupPath = $backupDirectory.'/expeditor-article-'.now()->format('Ymd-His').'.json';
    $backupPayload = json_encode($existingPost->getAttributes(), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

    if ($backupPayload === false || file_put_contents($backupPath, $backupPayload) === false) {
        throw new RuntimeException("Unable to write article backup: {$backupPath}");
    }
}

$post = BlogPost::query()->updateOrCreate(
    ['slug' => 'kak-budet-rabotat-ekspeditorskaya-kompaniya-s-1-sentyabrya-2026-goda'],
    [
        'title' => $title,
        'subtitle' => 'Поручение, договор с перевозчиком, ЭТрН и архив клиента — объясняем по шагам и без лишней канцелярщины.',
        'excerpt' => 'Что изменится для экспедитора с 1 сентября 2026 года: документы, роли участников и основные сценарии одного рейса простым языком.',
        'body' => $body,
        'cover_image_path' => 'images/blog/ekspeditor-2026/cover.png',
        'card_image_path' => 'images/blog/ekspeditor-2026/cover.png',
        'show_card_logo' => true,
        'card_logo_position' => 'top-left',
        'cover_image_alt' => 'Работа экспедиторской компании с электронными документами и ЭТрН с 1 сентября 2026 года',
        'author_name' => 'ЛогистРу',
        'author_type' => 'Organization',
        'author_url' => 'https://24logist.ru',
        'category' => $category->name,
        'blog_category_id' => $category->getKey(),
        'tags' => [
            'Экспедитор',
            'Экспедиторские документы',
            'Поручение экспедитору',
            'ЭТрН',
            'ГИС ЭПД',
            'ЭПД',
            'Логистика',
        ],
        'reading_time_minutes' => 18,
        'meta_title' => $meta('property', 'og:title') ?? $title,
        'meta_description' => $description,
        'meta_robots' => 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1',
        'canonical_url' => 'https://24logist.ru/blog/kak-budet-rabotat-ekspeditorskaya-kompaniya-s-1-sentyabrya-2026-goda',
        'og_title' => $meta('property', 'og:title') ?? $title,
        'og_description' => $meta('property', 'og:description') ?? $description,
        'og_image_path' => 'images/blog/ekspeditor-2026/cover.png',
        'og_type' => 'article',
        'twitter_title' => $meta('name', 'twitter:title') ?? $title,
        'twitter_description' => $meta('name', 'twitter:description') ?? $description,
        'twitter_image_path' => 'images/blog/ekspeditor-2026/cover.png',
        'twitter_card' => 'summary_large_image',
        'schema_type' => 'TechArticle',
        'schema_headline' => $title,
        'schema_description' => $description,
        'schema_image_path' => 'images/blog/ekspeditor-2026/cover.png',
        'is_published' => true,
        'is_featured' => false,
        'published_at' => now(),
        'sort_order' => 0,
    ],
);

echo json_encode([
    'id' => $post->getKey(),
    'slug' => $post->slug,
    'title' => $post->title,
    'published' => $post->is_published,
    'body_bytes' => strlen($post->body),
    'infographic_refs' => substr_count($post->body, 'basic-scenario.svg'),
    'backup' => $backupPath,
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT).PHP_EOL;
