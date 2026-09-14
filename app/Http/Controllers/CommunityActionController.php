<?php

namespace App\Http\Controllers;

use App\Models\CommunityComment;
use App\Models\CommunityPost;
use App\Models\CommunityReport;
use App\Models\CommunityNotification;
use App\Services\Community\CommunityVotingService;
use App\Services\Community\CommunitySocialService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CommunityActionController extends Controller
{
    public function vote(Request $request, CommunityVotingService $voting): JsonResponse
    {
        $user = auth('community')->user();
        abort_if($user->isRestricted(), 403);
        $data = $request->validate([
            'target_type' => ['required', 'in:post,comment'],
            'target_id' => ['required', 'integer', 'min:1'],
            'value' => ['required', 'integer', 'in:-1,0,1'],
        ]);

        return response()->json($voting->vote($user, $data['target_type'], $data['target_id'], $data['value']));
    }

    public function react(Request $request, CommunitySocialService $social): JsonResponse
    {
        $user = auth('community')->user();
        abort_if($user->isRestricted(), 403);
        $data = $request->validate([
            'target_type' => ['required', 'in:post,comment'],
            'target_id' => ['required', 'integer', 'min:1'],
            'code' => ['required', 'in:'.implode(',', array_keys(CommunitySocialService::REACTIONS))],
        ]);

        return response()->json($social->react($user, $data['target_type'], $data['target_id'], $data['code']));
    }

    public function award(Request $request, CommunitySocialService $social): RedirectResponse
    {
        $user = auth('community')->user();
        abort_if($user->isRestricted(), 403);
        $data = $request->validate([
            'target_type' => ['required', 'in:post,comment'],
            'target_id' => ['required', 'integer', 'min:1'],
            'code' => ['required', 'in:'.implode(',', array_keys(CommunitySocialService::AWARDS))],
            'message' => ['nullable', 'string', 'max:100'],
            'is_anonymous' => ['sometimes', 'boolean'],
        ]);

        $type = $data['target_type'];
        $id = (int) $data['target_id'];
        $target = $social->target($type, $id);
        $message = trim($data['message'] ?? '');
        $awardId = $social->award($user, $type, $id, $data['code'], $message === '' ? null : $message, $request->boolean('is_anonymous'));
        if ($awardId === null) {
            return back()->with('status', 'Вы уже наградили эту публикацию или комментарий.');
        }

        $url = $type === 'post' ? $target->getUrl() : $target->post->getUrl().'#comment-'.$id;
        $label = CommunitySocialService::AWARDS[$data['code']]['label'];
        CommunityNotification::query()->create([
            'community_user_id' => $target->community_user_id,
            'actor_id' => $request->boolean('is_anonymous') ? null : $user->id,
            'type' => 'award_received',
            'target_type' => 'award',
            'target_id' => $awardId,
            'data' => [
                'message' => ($request->boolean('is_anonymous') ? 'Вам анонимно вручили награду' : $user->displayName().' вручил вам награду').' «'.$label.'»'.($message !== '' ? ': '.$message : ''),
                'url' => $url,
            ],
        ]);

        return redirect($url)->with('status', 'Награда вручена. Спасибо за поддержку участника!');
    }

    public function report(Request $request): RedirectResponse
    {
        $user = auth('community')->user();
        $data = $request->validate([
            'target_type' => ['required', 'in:post,comment'],
            'target_id' => ['required', 'integer', 'min:1'],
            'reason' => ['required', 'in:spam,abuse,illegal,personal_data,other'],
            'details' => ['nullable', 'string', 'max:1000'],
        ]);

        $exists = match ($data['target_type']) {
            'post' => CommunityPost::query()->whereKey($data['target_id'])->exists(),
            'comment' => CommunityComment::query()->whereKey($data['target_id'])->exists(),
        };
        abort_unless($exists, 404);

        if (CommunityReport::query()
            ->where('community_user_id', $user->id)
            ->where('target_type', $data['target_type'])
            ->where('target_id', $data['target_id'])
            ->where('status', 'open')
            ->exists()) {
            throw ValidationException::withMessages(['reason' => 'Жалоба уже отправлена.']);
        }

        CommunityReport::query()->create($data + ['community_user_id' => $user->id]);

        return back()->with('status', 'Жалоба отправлена модераторам.');
    }
}
