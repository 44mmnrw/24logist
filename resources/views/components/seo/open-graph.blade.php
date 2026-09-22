@props([
    'landing' => null,
    'page' => null,
    'blogPost' => null,
    'communityPost' => null,
    'blogIndex' => false,
    'blogTag' => null,
    'blogCategory' => null,
    'notFound' => false,
    'robots' => null,
    'metadata' => null,
])

@php
    $meta = match (true) {
        $metadata !== null => $metadata,
        $communityPost !== null => \App\Support\OpenGraph::forCommunityPost($communityPost),
        $blogPost !== null => \App\Support\OpenGraph::forBlogPost($blogPost),
        $blogTag !== null => \App\Support\OpenGraph::forBlogTag($blogTag),
        $blogCategory !== null => \App\Support\OpenGraph::forBlogCategory($blogCategory),
        $blogIndex => \App\Support\OpenGraph::forBlogIndex(),
        $page !== null => \App\Support\OpenGraph::forPage($page),
        $notFound => \App\Support\OpenGraph::forNotFound(),
        default => \App\Support\OpenGraph::forLanding($landing),
    };
@endphp

@if ($robots !== null)
    @php($meta['robots'] = $robots)
@endif

<link rel="canonical" href="{{ $meta['url'] }}">
<link rel="alternate" type="text/plain" href="{{ url('/llms.txt') }}" title="LLMs.txt">

@if (filled($meta['robots'] ?? null))
    <meta name="robots" content="{{ $meta['robots'] }}">
@endif

@if (filled($meta['meta_description'] ?? $meta['description']))
    <meta name="description" content="{{ $meta['meta_description'] ?? $meta['description'] }}">
@endif

@if (filled($meta['keywords'] ?? null))
    <meta name="keywords" content="{{ $meta['keywords'] }}">
@endif

@if (filled($meta['author'] ?? null))
    <meta name="author" content="{{ $meta['author'] }}">
@endif

@if (filled($meta['ai_summary'] ?? null))
    <meta name="abstract" content="{{ $meta['ai_summary'] }}">
@endif

@if (filled($meta['google_site_verification'] ?? null))
    <meta name="google-site-verification" content="{{ $meta['google_site_verification'] }}">
@endif

@if (filled($meta['yandex_site_verification'] ?? null))
    <meta name="yandex-verification" content="{{ $meta['yandex_site_verification'] }}">
@endif

<meta property="og:type" content="{{ $meta['type'] }}">
<meta property="og:site_name" content="{{ $meta['site_name'] }}">
<meta property="og:locale" content="{{ $meta['locale'] }}">
<meta property="og:title" content="{{ $meta['title'] }}">
<meta property="og:url" content="{{ $meta['url'] }}">

@if (filled($meta['description']))
    <meta property="og:description" content="{{ $meta['description'] }}">
@endif

@if (filled($meta['image']))
    <meta property="og:image" content="{{ $meta['image'] }}">
    <meta property="og:image:secure_url" content="{{ $meta['image'] }}">
    @if (filled($meta['image_alt'] ?? null))
        <meta property="og:image:alt" content="{{ $meta['image_alt'] }}">
    @endif
    @if (filled($meta['image_width'] ?? null) && filled($meta['image_height'] ?? null))
        <meta property="og:image:width" content="{{ $meta['image_width'] }}">
        <meta property="og:image:height" content="{{ $meta['image_height'] }}">
    @endif
    @if (filled($meta['image_type'] ?? null))
        <meta property="og:image:type" content="{{ $meta['image_type'] }}">
    @endif
@endif

<meta name="twitter:card" content="{{ $meta['twitter_card'] ?? (filled($meta['image']) ? 'summary_large_image' : 'summary') }}">
@if (filled($meta['twitter_image'] ?? $meta['image']))
    <meta name="twitter:image" content="{{ $meta['twitter_image'] ?? $meta['image'] }}">
    @if (filled($meta['twitter_image_alt'] ?? null))
        <meta name="twitter:image:alt" content="{{ $meta['twitter_image_alt'] }}">
    @endif
@endif

<meta name="twitter:title" content="{{ $meta['twitter_title'] ?? $meta['title'] }}">
<meta name="twitter:url" content="{{ $meta['url'] }}">

@if (filled($meta['twitter_description'] ?? $meta['description']))
    <meta name="twitter:description" content="{{ $meta['twitter_description'] ?? $meta['description'] }}">
@endif

@if ($blogPost !== null)
    @if ($blogPost->published_at)
        <meta property="article:published_time" content="{{ $blogPost->published_at->toIso8601String() }}">
    @endif
    @if ($blogPost->updated_at)
        <meta property="article:modified_time" content="{{ $blogPost->updated_at->toIso8601String() }}">
    @endif
    @if (filled($blogPost->author_url))
        <meta property="article:author" content="{{ $blogPost->author_url }}">
    @elseif (filled($blogPost->author_name))
        <meta property="article:author" content="{{ $blogPost->author_name }}">
    @endif
    @if (filled($blogPost->displayCategory()))
        <meta property="article:section" content="{{ $blogPost->displayCategory() }}">
    @endif
    @foreach ((array) ($blogPost->tags ?? []) as $tag)
        @if (filled($tag))
            <meta property="article:tag" content="{{ $tag }}">
        @endif
    @endforeach
@endif

@if ($communityPost !== null)
    @if ($communityPost->published_at)
        <meta property="article:published_time" content="{{ $communityPost->published_at->toIso8601String() }}">
    @endif
    @if ($communityPost->edited_at ?: $communityPost->updated_at)
        <meta property="article:modified_time" content="{{ ($communityPost->edited_at ?: $communityPost->updated_at)->toIso8601String() }}">
    @endif
    @if ($communityPost->author)
        <meta property="article:author" content="{{ $communityPost->author->displayName() }}">
    @endif
    @if ($communityPost->category)
        <meta property="article:section" content="{{ $communityPost->category->name }}">
    @endif
@endif

@if (filled($meta['twitter_site'] ?? null))
    <meta name="twitter:site" content="{{ $meta['twitter_site'] }}">
@endif

@if (filled($meta['twitter_creator'] ?? null))
    <meta name="twitter:creator" content="{{ $meta['twitter_creator'] }}">
@endif
