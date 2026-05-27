<?php

namespace App\Http\Controllers;

use App\Services\CmsPageService;
use Illuminate\View\View;

class PageController extends Controller
{
    public function show(string $slug, CmsPageService $pages): View
    {
        $page = $pages->findPublished($slug) ?? abort(404);

        return view('pages.show', compact('page'));
    }
}
