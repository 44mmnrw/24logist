<?php

namespace App\Http\Controllers;

use App\Models\CommunityComment;
use App\Models\CommunityModerationAction;
use App\Models\CommunityPost;
use App\Models\CommunityReport;
use App\Models\CommunityUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CommunityModerationController extends Controller
{
    public function index(): View
    {
        $reports = CommunityReport::query()->where('status', 'open')->latest()->paginate(30);

        return view('community.moderation.index', compact('reports'));
    }

    public function act(Request $request, CommunityReport $report): RedirectResponse
    {
        $data = $request->validate([
            'action' => ['required', 'in:dismiss,hide,restore,lock,unlock,pin,unpin,suspend_1,suspend_7,suspend_30,ban'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $target = match ($report->target_type) {
            'post' => CommunityPost::withTrashed()->find($report->target_id),
            'comment' => CommunityComment::withTrashed()->find($report->target_id),
            default => null,
        };

        if ($data['action'] !== 'dismiss' && $target === null) {
            throw ValidationException::withMessages(['action' => 'Объект жалобы больше не существует.']);
        }

        $this->applyAction($target, $data['action']);
        CommunityModerationAction::query()->create([
            'community_user_id' => auth('community')->id(),
            'target_type' => $report->target_type,
            'target_id' => $report->target_id,
            'action' => $data['action'],
            'reason' => $data['reason'] ?? null,
            'metadata' => ['report_id' => $report->id],
        ]);
        $report->update([
            'status' => $data['action'] === 'dismiss' ? 'dismissed' : 'actioned',
            'resolved_at' => now(),
        ]);

        return back()->with('status', 'Действие модерации выполнено.');
    }

    private function applyAction(CommunityPost|CommunityComment|null $target, string $action): void
    {
        if ($target === null || $action === 'dismiss') {
            return;
        }

        if ($action === 'hide') {
            $target->update(['status' => 'hidden']);
        } elseif ($action === 'restore') {
            $target->update(['status' => 'published']);
        } elseif ($target instanceof CommunityPost && in_array($action, ['lock', 'unlock'], true)) {
            $target->update(['locked_at' => $action === 'lock' ? now() : null]);
        } elseif ($target instanceof CommunityPost && in_array($action, ['pin', 'unpin'], true)) {
            $target->update(['is_pinned' => $action === 'pin']);
        } elseif (str_starts_with($action, 'suspend_') || $action === 'ban') {
            $user = CommunityUser::query()->find($target->community_user_id);

            if ($user !== null) {
                $days = (int) str_replace('suspend_', '', $action);
                $user->update($action === 'ban'
                    ? ['banned_at' => now(), 'suspended_until' => null]
                    : ['suspended_until' => now()->addDays($days)]);
            }
        }
    }
}
