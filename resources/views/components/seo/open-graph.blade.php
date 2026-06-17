@props([
    'landing' => null,
    'page' => null,
    'blogPost' => null,
    'blogIndex' => false,
    'notFound' => false,
])

@php
    $meta = match (true) {
        $blogPost !== null => \App\Support\OpenGraph::forBlogPost($blogPost),
        $blogIndex => \App\Support\OpenGraph::forBlogIndex(),
        $page !== null => \App\Support\OpenGraph::forPage($page),
        $notFound => \App\Support\OpenGraph::forNotFound(),
        default => \App\Support\OpenGraph::forLanding($landing),
    };
@endphp

<link rel="canonical" href="{{ $meta['url'] }}">
<link rel="alternate" type="text/plain" href="{{ url('/llms.txt') }}" title="LLMs.txt">

@if (filled($meta['robots'] ?? null))
    <meta name="robots" content="{{ $meta['robots'] }}">
@endif

@if (filled($meta['description']))
    <meta name="description" content="{{ $meta['description'] }}">
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
    @if (filled($meta['image_width'] ?? null) && filled($meta['image_height'] ?? null))
        <meta property="og:image:width" content="{{ $meta['image_width'] }}">
        <meta property="og:image:height" content="{{ $meta['image_height'] }}">
    @endif
    @if (filled($meta['image_type'] ?? null))
        <meta property="og:image:type" content="{{ $meta['image_type'] }}">
    @endif
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:image" content="{{ $meta['image'] }}">
@else
    <meta name="twitter:card" content="summary">
@endif

<meta name="twitter:title" content="{{ $meta['title'] }}">
<meta name="twitter:url" content="{{ $meta['url'] }}">

@if (filled($meta['description']))
    <meta name="twitter:description" content="{{ $meta['description'] }}">
@endif

@if ($blogPost !== null)
    @if ($blogPost->published_at)
        <meta property="article:published_time" content="{{ $blogPost->published_at->toIso8601String() }}">
    @endif
    @if ($blogPost->updated_at)
        <meta property="article:modified_time" content="{{ $blogPost->updated_at->toIso8601String() }}">
    @endif
    @if (filled($blogPost->author_name))
        <meta property="article:author" content="{{ $blogPost->author_name }}">
    @endif
    @if (filled($blogPost->category))
        <meta property="article:section" content="{{ $blogPost->category }}">
    @endif
    @foreach ((array) ($blogPost->tags ?? []) as $tag)
        @if (filled($tag))
            <meta property="article:tag" content="{{ $tag }}">
        @endif
    @endforeach
@endif

@if (filled($meta['twitter_site'] ?? null))
    <meta name="twitter:site" content="{{ $meta['twitter_site'] }}">
@endif

@if (filled($meta['twitter_creator'] ?? null))
    <meta name="twitter:creator" content="{{ $meta['twitter_creator'] }}">
@endif
