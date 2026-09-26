<?php

namespace App\Services\Community;

use App\Models\CommunityCategory;
use App\Models\CommunityPost;
use App\Models\CommunitySeoPage;
use App\Models\CommunityUser;
use App\Support\OpenGraph;
use Illuminate\Http\Request;

final class CommunitySeoService
{
    public function current(Request $request): array
    {
        if ($request->attributes->has('community_seo')) {
            return $request->attributes->get('community_seo');
        }

        $route = $request->route()?->getName() ?? '';
        $source = match ($route) {
            'community.categories.show' => $request->route('category'),
            'community.posts.show' => $request->route('post'),
            'community.profile' => $request->route('user'),
            default => null,
        };
        $key = match (true) {
            $source instanceof CommunityCategory => 'category:'.$source->id,
            $source instanceof CommunityPost => 'post:'.$source->id,
            $source instanceof CommunityUser => 'profile:'.$source->id,
            default => $route,
        };
        $page = CommunitySeoPage::query()->where('page_key', $key)->first();
        $settings = array_filter($page?->settings ?? [], fn ($value): bool => filled($value));
        $label = CommunitySeoRegistry::PAGES[$route][0] ?? 'Сообщество логистРу';
        $description = 'Обсуждения перевозок, электронных документов и цифровой логистики в сообществе логистРу.';
        $type = 'WebPage';
        $public = (CommunitySeoRegistry::PAGES[$route][1] ?? null) === 'page';
        $url = $request->url();
        $robots = OpenGraph::ROBOTS_INDEX;

        if ($source instanceof CommunityCategory) {
            $label = $source->name;
            $description = $source->description ?: 'Обсуждения по теме «'.$label.'» в сообществе логистРу.';
            $public = $source->is_active;
            $type = 'CollectionPage';
            $url = route('community.categories.show', $source);
        } elseif ($source instanceof CommunityPost) {
            $label = $source->title;
            $public = $source->status === 'published' && ! $source->trashed();
            $type = 'DiscussionForumPosting';
            $url = $source->getUrl();
        } elseif ($source instanceof CommunityUser) {
            $label = $source->displayName();
            $description = $source->bio ?: 'Публикации и обсуждения участника '.$label.' в сообществе логистРу.';
            $public = $source->isOnboarded() && ! $source->trashed();
            $robots = 'noindex, follow';
            $type = 'ProfilePage';
            $url = route('community.profile', $source);
        } elseif ($route === 'community.index') {
            $type = 'CollectionPage';
        } elseif ($route === 'community.rules') {
            $description = 'Правила публикации тем, комментариев и общения в сообществе логистРу.';
        } elseif ($route === 'community.privacy') {
            $description = 'Как обрабатываются и защищаются персональные данные участников сообщества логистРу.';
        } elseif ($route === 'community.posts.edit') {
            $label = 'Редактирование темы';
        }

        $defaults = $source instanceof CommunityPost
            ? OpenGraph::forCommunityPost($source)
            : [
                'html_title' => $label.' — логистРу', 'title' => $label.' — логистРу',
                'description' => $description, 'url' => $url, 'robots' => $robots,
            ];
        if ($source instanceof CommunityPost) {
            $defaults['meta_description'] = $source->meta_description ?: app(CommunityPostSeoService::class)->metadata($source)['meta_description'];
        }
        $meta = OpenGraph::forCommunityPage($defaults, $settings);
        $meta['h1'] = $settings['seo_h1'] ?? $label;

        // Private workflows never become indexable through an SEO override.
        if (! $public) {
            $meta['robots'] = OpenGraph::ROBOTS_NOINDEX;
        } elseif ($request->hasAny(['q', 'sort', 'period', 'page', 'comment_sort'])) {
            $meta['robots'] = preg_match('/\b(nofollow|none)\b/i', $meta['robots'])
                ? 'noindex, nofollow'
                : 'noindex, follow';
        }

        $schemaType = $settings['schema_type'] ?? $type;
        if (! in_array($schemaType, ['WebPage', 'CollectionPage', 'DiscussionForumPosting', 'ProfilePage'], true)) {
            $schemaType = $type;
        }
        $graph = [
            '@context' => 'https://schema.org', '@type' => $schemaType,
            '@id' => $meta['url'].'#webpage', 'url' => $meta['url'],
            'name' => $settings['schema_headline'] ?? $meta['h1'],
            'description' => $settings['schema_description'] ?? $meta['meta_description'],
            'inLanguage' => 'ru-RU',
        ];
        $image = OpenGraph::absolutePublicUrl($settings['schema_image_path'] ?? null) ?? $meta['image'];
        if ($image) {
            $graph['image'] = $image;
        }
        if ($source instanceof CommunityPost && $schemaType === 'DiscussionForumPosting') {
            $graph += [
                'headline' => $graph['name'],
                'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => $meta['url']],
                'datePublished' => $source->published_at?->toIso8601String(),
                'dateModified' => ($source->edited_at ?: $source->updated_at)?->toIso8601String(),
                'author' => ['@type' => 'Person', 'name' => $source->author?->displayName() ?: '[удалён]'],
                'commentCount' => $source->comments_count,
                'interactionStatistic' => ['@type' => 'InteractionCounter', 'interactionType' => 'https://schema.org/LikeAction', 'userInteractionCount' => max(0, $source->score)],
            ];
        }
        if ($source instanceof CommunityUser && $schemaType === 'ProfilePage') {
            $graph['mainEntity'] = ['@type' => 'Person', 'name' => $source->displayName(), 'url' => $url];
        }
        $meta['schema'] = $public && ($settings['schema_enabled'] ?? true) ? $graph : null;
        $request->attributes->set('community_seo', $meta);

        return $meta;
    }
}
