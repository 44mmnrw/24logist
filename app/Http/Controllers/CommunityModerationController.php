<?php

namespace App\Http\Controllers;

use App\Models\CommunityComment;
use App\Models\CommunityModerationAction;
use App\Models\CommunityPost;
use App\Models\CommunityReport;
use App\Models\CommunityUser;
use App\Services\Community\CommunityCommentCounter;
use App\Services\Community\CommunityKarmaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CommunityModerationController extends Controller
{
    public function index(): View
    {
        $reports = CommunityReport::query()->where('status', 'open')->latest()->paginate(30);
        $targets = [
            'post' => CommunityPost::withTrashed()->with('photos')->whereIn('id', $reports->getCollection()->where('target_type', 'post')->pluck('target_id'))->get()->keyBy('id'),
            'comment' => CommunityComment::withTrashed()->with(['post', 'photos'])->whereIn('id', $reports->getCollection()->where('target_type', 'comment')->pluck('target_id'))->get()->keyBy('id'),
        ];

        return view('community.moderation.index', compact('reports', 'targets'));
    }

    public function act(
        Request $request,
        CommunityReport $report,
        CommunityCommentCounter $counter,
        CommunityKarmaService $karma,
    ): RedirectResponse {
        $data = $request->validate([
            'action' => ['required', 'in:dismiss,hide,restore,lock,unlock,pin,unpin,suspend_1,suspend_7,suspend_30,ban'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($report, $data, $counter): void {
            $lockedReport = CommunityReport::query()->whereKey($report->id)->lockForUpdate()->firstOrFail();
            if ($lockedReport->status !== 'open') {
                throw ValidationException::withMessages(['action' => 'Жалоба уже рассмотрена.']);
            }

            $target = match ($lockedReport->target_type) {
                'post' => CommunityPost::withTrashed()->find($lockedReport->target_id),
                'comment' => CommunityComment::withTrashed()->find($lockedReport->target_id),
                default => null,
            };

            $this->validateAction($target, $data['action']);
            $this->applyAction($target, $data['action'], $counter);
            CommunityModerationAction::query()->create([
                'community_user_id' => auth('community')->id(),
                'target_type' => $lockedReport->target_type,
                'target_id' => $lockedReport->target_id,
                'action' => $data['action'],
                'reason' => $data['reason'] ?? null,
                'metadata' => ['report_id' => $lockedReport->id],
            ]);
            $lockedReport->update([
                'status' => $data['action'] === 'dismiss' ? 'dismissed' : 'actioned',
                'resolved_at' => now(),
            ]);
        });

        $target = match ($report->target_type) {
            'post' => CommunityPost::withTrashed()->find($report->target_id),
            'comment' => CommunityComment::withTrashed()->find($report->target_id),
            default => null,
        };
        if ($target instanceof CommunityPost) {
            $karma->recalculateMany(
                $target->comments()->withTrashed()->pluck('community_user_id')->push($target->community_user_id),
            );
        } elseif ($target instanceof CommunityComment) {
            $karma->recalculateMany([
                $target->community_user_id,
                CommunityPost::withTrashed()->whereKey($target->community_post_id)->value('community_user_id'),
            ]);
        }

        return back()->with('status', 'Действие модерации выполнено.');
    }

    private function validateAction(CommunityPost|CommunityComment|null $target, string $action): void
    {
        if ($action === 'dismiss') {
            return;
        }

        if ($target === null || $target->trashed() || $target->status === 'deleted') {
            throw ValidationException::withMessages(['action' => 'Объект жалобы больше недоступен для этого действия.']);
        }

        if (in_array($action, ['lock', 'unlock', 'pin', 'unpin'], true) && ! $target instanceof CommunityPost) {
            throw ValidationException::withMessages(['action' => 'Это действие доступно только для темы.']);
        }

        if ($action === 'hide' && $target->status !== 'published') {
            throw ValidationException::withMessages(['action' => 'Скрыть можно только опубликованный материал.']);
        }

        if ($action === 'restore' && $target->status !== 'hidden') {
            throw ValidationException::withMessages(['action' => 'Восстановить можно только скрытый материал.']);
        }

        if ((str_starts_with($action, 'suspend_') || $action === 'ban') && $target->community_user_id === null) {
            throw ValidationException::withMessages(['action' => 'У этого материала больше нет автора.']);
        }
    }

    private function applyAction(CommunityPost|CommunityComment|null $target, string $action, CommunityCommentCounter $counter): void
    {
        if ($target === null || $action === 'dismiss') {
            return;
        }

        if ($action === 'hide' || $action === 'restore') {
            $post = $target instanceof CommunityComment
                ? CommunityPost::query()->whereKey($target->community_post_id)->lockForUpdate()->firstOrFail()
                : null;
            $target->update(['status' => $action === 'hide' ? 'hidden' : 'published']);
            if ($post !== null) {
                if ($action === 'hide' && $post->accepted_comment_id === $target->id) {
                    $post->update(['accepted_comment_id' => null, 'resolved_at' => null]);
                }
                $counter->sync($post);
            }
        } elseif ($target instanceof CommunityPost && in_array($action, ['lock', 'unlock'], true)) {
            $target->update(['locked_at' => $action === 'lock' ? now() : null]);
        } elseif ($target instanceof CommunityPost && in_array($action, ['pin', 'unpin'], true)) {
            $target->update(['is_pinned' => $action === 'pin']);
        } elseif (str_starts_with($action, 'suspend_') || $action === 'ban') {
            $user = CommunityUser::query()->findOrFail($target->community_user_id);
            $days = (int) str_replace('suspend_', '', $action);
            $user->update($action === 'ban'
                ? ['banned_at' => now(), 'suspended_until' => null]
                : ['suspended_until' => now()->addDays($days)]);
        }
    }
}
