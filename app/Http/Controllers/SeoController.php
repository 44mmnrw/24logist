<?php

namespace App\Http\Controllers;

use App\Services\LlmsTxtService;
use App\Services\SitemapService;
use Illuminate\Http\Response;

class SeoController extends Controller
{
    public function robots(SitemapService $sitemap): Response
    {
        return response($sitemap->robots(), 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

    public function sitemap(SitemapService $sitemap): Response
    {
        return response($sitemap->xml(), 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

    public function llms(LlmsTxtService $llms): Response
    {
        return response($llms->generate(), 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
