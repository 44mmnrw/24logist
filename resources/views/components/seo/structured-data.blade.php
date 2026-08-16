@props([
    'landing' => null,
    'page' => null,
    'blogPost' => null,
    'blogIndex' => false,
    'blogTag' => null,
    'notFound' => false,
])

@php
    $graphs = match (true) {
        $notFound => [],
        $blogPost !== null => \App\Support\StructuredData::forBlogPost($blogPost),
        $blogTag !== null => \App\Support\StructuredData::forBlogTag($blogTag),
        $blogIndex => \App\Support\StructuredData::forBlogIndex(),
        $page !== null => \App\Support\StructuredData::forPage($page),
        default => \App\Support\StructuredData::forLanding($landing),
    };
@endphp

@foreach ($graphs as $graph)
    <script type="application/ld+json">{!! json_encode($graph, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) !!}</script>
@endforeach
