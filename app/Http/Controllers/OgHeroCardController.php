<?php

namespace App\Http\Controllers;

use App\Services\LandingPageService;
use App\Support\OpenGraphHeroCard;
use Illuminate\View\View;

class OgHeroCardController extends Controller
{
    public function __invoke(LandingPageService $landing): View
    {
        return view('seo.og-hero-card', [
            'card' => OpenGraphHeroCard::data($landing),
        ]);
    }
}
