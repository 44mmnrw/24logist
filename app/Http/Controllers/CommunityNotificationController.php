<?php

namespace App\Http\Controllers;

use App\Models\CommunityNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CommunityNotificationController extends Controller
{
    public function index(): View
    {
        $notifications = auth('community')->user()
            ->communityNotifications()
            ->with('deliveries')
            ->latest()
            ->paginate(30);

        return view('community.notifications', compact('notifications'));
    }

    public function read(CommunityNotification $notification): RedirectResponse
    {
        abort_unless($notification->community_user_id === auth('community')->id(), 403);
        $notification->update(['read_at' => now()]);

        return redirect()->away((string) ($notification->data['url'] ?? route('community.notifications')));
    }

    public function readAll(): RedirectResponse
    {
        auth('community')->user()->communityNotifications()->whereNull('read_at')->update(['read_at' => now()]);

        return back()->with('status', 'Все уведомления отмечены прочитанными.');
    }
}
