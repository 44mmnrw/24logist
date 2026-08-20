<?php

declare(strict_types=1);

use App\Models\BlogPost;
use Illuminate\Contracts\Console\Kernel;

chdir('/var/www/logist_sys/data/www/24logist.ru/.app');

require 'vendor/autoload.php';

$app = require 'bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$post = BlogPost::query()
    ->where('slug', 'prakticeskoe-rukovodstvo')
    ->firstOrFail();

$coverImage = $post->cover_image_path;

$post->forceFill([
    'meta_title' => 'ЭТрН с 1 сентября 2026: руководство для участников перевозок',
    'meta_description' => 'Практическое руководство по переходу на ЭТрН с 1 сентября 2026 года: роли участников, титулы Т1–Т4, электронные подписи и чек-лист подготовки.',
    'meta_keywords' => 'ЭТрН, электронная транспортная накладная, ГИС ЭПД, электронные перевозочные документы, транспортный ЭДО, грузоперевозки, 1 сентября 2026',
    'meta_robots' => 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1',
    'canonical_url' => 'https://24logist.ru/blog/prakticeskoe-rukovodstvo',
    'og_title' => 'ЭТрН с 1 сентября 2026: руководство для участников перевозок',
    'og_description' => 'Практическое руководство по переходу на ЭТрН с 1 сентября 2026 года: роли участников, титулы Т1–Т4, электронные подписи и чек-лист подготовки.',
    'og_image_path' => $coverImage,
    'og_type' => 'article',
    'twitter_title' => 'ЭТрН с 1 сентября 2026 года: практическое руководство для участников грузоперевозок',
    'twitter_description' => 'Когда нужна ЭТрН, кто подписывает титулы Т1–Т4 и как подготовить компанию к обязательному электронному документообороту с 1 сентября 2026 года.',
    'twitter_image_path' => $coverImage,
    'twitter_card' => 'summary_large_image',
    'schema_type' => 'TechArticle',
    'schema_headline' => 'ЭТрН с 1 сентября 2026 года: практическое руководство для участников грузоперевозок',
    'schema_description' => 'Практическое руководство по переходу на ЭТрН с 1 сентября 2026 года: роли участников, титулы Т1–Т4, электронные подписи и чек-лист подготовки.',
    'schema_image_path' => $coverImage,
])->save();

echo json_encode([
    'updated' => true,
    'id' => $post->id,
    'slug' => $post->slug,
    'cover_image_path' => $coverImage,
    'meta_title' => $post->meta_title,
    'canonical_url' => $post->canonical_url,
    'og_type' => $post->og_type,
    'twitter_card' => $post->twitter_card,
    'schema_type' => $post->schema_type,
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR).PHP_EOL;
