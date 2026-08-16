<?php

namespace App\Support;

use App\Models\BlogPost;
use App\Models\BlogTag;
use App\Models\CmsPage;
use App\Services\LandingPageService;
use App\Services\SiteSettingsService;

final class StructuredData
{
    private const CONTEXT = 'https://schema.org';

    /**
     * @return list<array<string, mixed>>
     */
    public static function forLanding(?LandingPageService $landing = null): array
    {
        $landing ??= app(LandingPageService::class);

        return array_values(array_filter([
            self::organization(),
            self::website(),
            self::softwareApplication(),
            self::webPage(
                name: self::pageTitle(OpenGraph::forLanding($landing)['title']),
                description: OpenGraph::forLanding($landing)['description'],
                url: OpenGraph::forLanding($landing)['url'],
                type: 'WebPage',
            ),
            self::faqPage($landing),
        ]));
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function forPage(CmsPage $page): array
    {
        $meta = OpenGraph::forPage($page);
        $pageType = $page->slug === 'contacts' ? 'ContactPage' : 'WebPage';

        return array_values(array_filter([
            self::organization(),
            self::website(),
            self::webPage(
                name: self::pageTitle($meta['title']),
                description: $meta['description'],
                url: $meta['url'],
                type: $pageType,
            ),
            self::breadcrumbList($page, $meta['url']),
        ]));
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function forBlogIndex(): array
    {
        $meta = OpenGraph::forBlogIndex();

        return array_values(array_filter([
            self::organization(),
            self::website(),
            self::webPage(
                name: self::pageTitle($meta['title']),
                description: $meta['description'],
                url: $meta['url'],
                type: 'Blog',
            ),
            self::breadcrumbListForBlog($meta['url']),
        ]));
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function forBlogPost(BlogPost $post): array
    {
        $meta = OpenGraph::forBlogPost($post);

        return array_values(array_filter([
            self::organization(),
            self::website(),
            self::article($post, $meta),
            self::breadcrumbListForBlogPost($post, $meta['url']),
        ]));
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function forBlogTag(BlogTag $tag): array
    {
        $meta = OpenGraph::forBlogTag($tag);
        $type = in_array($tag->schema_type, ['CollectionPage', 'WebPage'], true)
            ? (string) $tag->schema_type
            : 'CollectionPage';

        $page = self::webPage(
            name: self::pageTitle($tag->schema_headline ?: $meta['html_title']),
            description: $tag->schema_description ?: $meta['description'],
            url: $meta['url'],
            type: $type,
        );
        $schemaImage = OpenGraph::absolutePublicUrl($tag->schema_image_path) ?? $meta['image'];

        if ($page !== null && filled($schemaImage)) {
            $page['primaryImageOfPage'] = [
                '@type' => 'ImageObject',
                'url' => $schemaImage,
            ];
            $page['image'] = $schemaImage;
        }

        return array_values(array_filter([
            self::organization(),
            self::website(),
            $page,
            self::breadcrumbListForBlogTag($tag, $meta['url']),
        ]));
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function organization(): ?array
    {
        $settings = app(SiteSettingsService::class)->get();
        $legalName = trim((string) ($settings->org_legal_name ?? ''));
        $brandName = trim((string) ($settings->org_brand_name ?? '')) ?: OpenGraph::SITE_NAME;

        if ($legalName === '' && $brandName === '') {
            return null;
        }

        $data = [
            '@context' => self::CONTEXT,
            '@type' => 'Organization',
            '@id' => self::siteUrl().'#organization',
            'name' => $brandName,
            'url' => self::siteUrl(),
        ];

        if ($legalName !== '' && $legalName !== $brandName) {
            $data['legalName'] = $legalName;
        }

        if (filled($settings->org_email)) {
            $data['email'] = (string) $settings->org_email;
        }

        if (filled($settings->org_phone)) {
            $data['telephone'] = (string) $settings->org_phone;
        }

        $logo = OpenGraph::absolutePublicUrl($settings->org_logo_path ?? $settings->og_image_path);

        if ($logo !== null) {
            $data['logo'] = [
                '@type' => 'ImageObject',
                'url' => $logo,
            ];
        }

        $address = self::postalAddress($settings);

        if ($address !== null) {
            $data['address'] = $address;
        }

        $sameAs = self::parseSameAs($settings->org_same_as);

        if ($sameAs !== []) {
            $data['sameAs'] = $sameAs;
        }

        if (filled($settings->org_inn)) {
            $data['taxID'] = (string) $settings->org_inn;
        }

        if (filled($settings->org_ogrn)) {
            $data['identifier'] = [
                '@type' => 'PropertyValue',
                'propertyID' => 'OGRN',
                'value' => (string) $settings->org_ogrn,
            ];
        }

        return $data;
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function website(): ?array
    {
        return [
            '@context' => self::CONTEXT,
            '@type' => 'WebSite',
            '@id' => self::siteUrl().'#website',
            'url' => self::siteUrl(),
            'name' => app(SiteSettingsService::class)->get()->org_brand_name ?: OpenGraph::SITE_NAME,
            'publisher' => [
                '@id' => self::siteUrl().'#organization',
            ],
            'inLanguage' => 'ru-RU',
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function softwareApplication(): ?array
    {
        $settings = app(SiteSettingsService::class)->get();
        $hero = app(LandingPageService::class)->section('hero');
        $description = filled($settings->og_description)
            ? trim((string) $settings->og_description)
            : trim(strip_tags((string) ($hero?->subtitle ?: $hero?->description)));

        $data = [
            '@context' => self::CONTEXT,
            '@type' => 'SoftwareApplication',
            'name' => $settings->org_brand_name ?: OpenGraph::SITE_NAME,
            'applicationCategory' => 'BusinessApplication',
            'operatingSystem' => 'Web',
            'url' => self::siteUrl(),
            'provider' => [
                '@id' => self::siteUrl().'#organization',
            ],
        ];

        if ($description !== '') {
            $data['description'] = $description;
        }

        return $data;
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function webPage(string $name, ?string $description, string $url, string $type): ?array
    {
        $data = [
            '@context' => self::CONTEXT,
            '@type' => $type,
            '@id' => $url.'#webpage',
            'url' => $url,
            'name' => $name,
            'isPartOf' => [
                '@id' => self::siteUrl().'#website',
            ],
            'inLanguage' => 'ru-RU',
        ];

        if (filled($description)) {
            $data['description'] = $description;
        }

        return $data;
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function breadcrumbList(CmsPage $page, string $url): ?array
    {
        return [
            '@context' => self::CONTEXT,
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                [
                    '@type' => 'ListItem',
                    'position' => 1,
                    'name' => 'Главная',
                    'item' => self::siteUrl(),
                ],
                [
                    '@type' => 'ListItem',
                    'position' => 2,
                    'name' => $page->title,
                    'item' => $url,
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function article(BlogPost $post, array $meta): ?array
    {
        $type = in_array($post->schema_type, ['Article', 'BlogPosting', 'NewsArticle', 'TechArticle'], true)
            ? (string) $post->schema_type
            : 'Article';

        $headline = filled($post->schema_headline)
            ? (string) $post->schema_headline
            : $post->title;

        $description = filled($post->schema_description)
            ? (string) $post->schema_description
            : ($meta['description'] ?? null);

        $image = filled($post->schema_image_path)
            ? OpenGraph::absolutePublicUrl($post->schema_image_path)
            : ($meta['image'] ?? null);

        $data = [
            '@context' => self::CONTEXT,
            '@type' => $type,
            '@id' => $meta['url'].'#article',
            'mainEntityOfPage' => [
                '@id' => $meta['url'].'#webpage',
            ],
            'headline' => self::pageTitle($headline),
            'url' => $meta['url'],
            'inLanguage' => 'ru-RU',
            'isAccessibleForFree' => true,
            'publisher' => [
                '@id' => self::siteUrl().'#organization',
            ],
        ];

        if (filled($description)) {
            $data['description'] = $description;
        }

        if (filled($image)) {
            $data['image'] = $image;
        }

        if ($post->published_at !== null) {
            $data['datePublished'] = $post->published_at->toIso8601String();
        }

        if ($post->updated_at !== null) {
            $data['dateModified'] = $post->updated_at->toIso8601String();
        }

        if (filled($post->author_name)) {
            $data['author'] = [
                '@type' => $post->author_type === 'Organization' ? 'Organization' : 'Person',
                'name' => (string) $post->author_name,
            ];

            if (filled($post->author_url)) {
                $data['author']['url'] = (string) $post->author_url;
            }
        }

        if (filled($post->displayCategory())) {
            $data['articleSection'] = (string) $post->displayCategory();
        }

        if ($post->reading_time_minutes !== null) {
            $data['timeRequired'] = 'PT'.max(1, (int) $post->reading_time_minutes).'M';
        }

        $tags = array_values(array_filter((array) $post->tags, fn ($tag): bool => filled($tag)));

        if ($tags !== []) {
            $data['keywords'] = implode(', ', $tags);
        }

        $body = trim(strip_tags($post->renderBody()));

        if ($body !== '') {
            preg_match_all('/[\pL\pN]+/u', $body, $words);

            $data['wordCount'] = count($words[0] ?? []);
        }

        return $data;
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function breadcrumbListForBlog(string $url): ?array
    {
        return [
            '@context' => self::CONTEXT,
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                [
                    '@type' => 'ListItem',
                    'position' => 1,
                    'name' => 'Главная',
                    'item' => self::siteUrl(),
                ],
                [
                    '@type' => 'ListItem',
                    'position' => 2,
                    'name' => 'Блог',
                    'item' => $url,
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function breadcrumbListForBlogPost(BlogPost $post, string $url): ?array
    {
        return [
            '@context' => self::CONTEXT,
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                [
                    '@type' => 'ListItem',
                    'position' => 1,
                    'name' => 'Главная',
                    'item' => self::siteUrl(),
                ],
                [
                    '@type' => 'ListItem',
                    'position' => 2,
                    'name' => 'Блог',
                    'item' => route('blog.index'),
                ],
                [
                    '@type' => 'ListItem',
                    'position' => 3,
                    'name' => $post->title,
                    'item' => $url,
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function breadcrumbListForBlogTag(BlogTag $tag, string $url): ?array
    {
        return [
            '@context' => self::CONTEXT,
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                [
                    '@type' => 'ListItem',
                    'position' => 1,
                    'name' => 'Главная',
                    'item' => self::siteUrl(),
                ],
                [
                    '@type' => 'ListItem',
                    'position' => 2,
                    'name' => 'Блог',
                    'item' => route('blog.index'),
                ],
                [
                    '@type' => 'ListItem',
                    'position' => 3,
                    'name' => $tag->name,
                    'item' => $url,
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function faqPage(?LandingPageService $landing): ?array
    {
        $landing ??= app(LandingPageService::class);
        $items = $landing->blocks('faq', 'faq');

        if ($items->isEmpty()) {
            return null;
        }

        $entities = [];

        foreach ($items as $item) {
            $question = trim((string) $item->title);
            $answer = trim(strip_tags((string) $item->description));

            if ($question === '' || $answer === '') {
                continue;
            }

            $entities[] = [
                '@type' => 'Question',
                'name' => $question,
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $answer,
                ],
            ];
        }

        if ($entities === []) {
            return null;
        }

        return [
            '@context' => self::CONTEXT,
            '@type' => 'FAQPage',
            'mainEntity' => $entities,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function postalAddress(object $settings): ?array
    {
        $street = trim((string) ($settings->org_street_address ?? ''));
        $locality = trim((string) ($settings->org_address_locality ?? ''));

        if ($street === '' && $locality === '') {
            return null;
        }

        $address = [
            '@type' => 'PostalAddress',
            'addressCountry' => strtoupper((string) ($settings->org_address_country ?: 'RU')),
        ];

        if ($street !== '') {
            $address['streetAddress'] = $street;
        }

        if ($locality !== '') {
            $address['addressLocality'] = $locality;
        }

        if (filled($settings->org_address_region)) {
            $address['addressRegion'] = (string) $settings->org_address_region;
        }

        if (filled($settings->org_postal_code)) {
            $address['postalCode'] = (string) $settings->org_postal_code;
        }

        return $address;
    }

    /**
     * @return list<string>
     */
    private static function parseSameAs(?string $value): array
    {
        if (! filled($value)) {
            return [];
        }

        $urls = preg_split('/\R+/', (string) $value) ?: [];

        return array_values(array_filter(array_map(
            static fn (string $url): string => trim($url),
            $urls,
        )));
    }

    private static function siteUrl(): string
    {
        return rtrim((string) config('app.url'), '/');
    }

    private static function pageTitle(string $title): string
    {
        return html_entity_decode(strip_tags($title), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}
