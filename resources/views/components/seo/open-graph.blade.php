@props([
    'landing' => null,
    'page' => null,
])

@php
    $meta = $page !== null
        ? \App\Support\OpenGraph::forPage($page)
        : \App\Support\OpenGraph::forLanding($landing);
@endphp

<link rel="canonical" href="{{ $meta['url'] }}">
<meta property="og:type" content="{{ $meta['type'] }}">
<meta property="og:site_name" content="{{ $meta['site_name'] }}">
<meta property="og:locale" content="{{ $meta['locale'] }}">
<meta property="og:title" content="{{ $meta['title'] }}">
<meta property="og:url" content="{{ $meta['url'] }}">
@if (filled($meta['description']))
    <meta property="og:description" content="{{ $meta['description'] }}">
    <meta name="description" content="{{ $meta['description'] }}">
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
@if (filled($meta['description']))
    <meta name="twitter:description" content="{{ $meta['description'] }}">
@endif
