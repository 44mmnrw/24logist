<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class CommunityLegalController extends Controller
{
    public function rules(): View
    {
        return view('community.legal.rules');
    }

    public function privacy(): View
    {
        return view('community.legal.privacy');
    }
}
