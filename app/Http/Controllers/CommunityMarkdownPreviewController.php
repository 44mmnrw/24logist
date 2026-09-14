<?php

namespace App\Http\Controllers;

use App\Services\Community\CommunityContentRenderer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CommunityMarkdownPreviewController
{
    public function __invoke(Request $request, CommunityContentRenderer $renderer): JsonResponse
    {
        $data = $request->validate([
            'body_markdown' => ['nullable', 'string', 'max:'.config('community.limits.comment_body')],
        ]);

        return response()->json([
            'html' => $renderer->render($data['body_markdown'] ?? null),
        ])->header('Cache-Control', 'no-store');
    }
}
