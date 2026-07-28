<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CsrfTokenController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $response = response()->json([
            'token' => $request->session()->token(),
        ]);
        $response->headers->set('Cache-Control', 'no-store, private');
        $response->headers->set('Pragma', 'no-cache');

        return $response;
    }
}
