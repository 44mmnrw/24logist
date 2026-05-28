<?php

namespace App\Http\Controllers;

use App\Services\CmsPageService;
use Illuminate\View\View;

class PageController extends Controller
{
    public function show(string $slug, CmsPageService $pages): View
    {
        $page = $pages->findPublished($slug) ?? abort(404);

        $template = $slug === 'contacts' ? 'pages.contacts' : 'pages.show';

        return view($template, compact('page'));
    }
}
