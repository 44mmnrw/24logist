<?php

namespace App\Http\Controllers;

use App\Models\ReferralClick;
use App\Models\ReferralParticipant;
use App\Models\ReferralProgramSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class ReferralPortalController extends Controller
{
    public function show(ReferralParticipant $participant): Response
    {
        $participant->load([
            'terms',
            'placements' => fn ($query) => $query->latest(),
            'attributions' => fn ($query) => $query->with('commissions')->latest('attributed_at'),
            'payouts' => fn ($query) => $query->latest('period_end'),
        ]);
        $commissions = $participant->attributions->flatMap->commissions;

        return response()->view('referrals.portal', [
            'participant' => $participant,
            'settings' => ReferralProgramSetting::current(),
            'personalLink' => url('/r/'.$participant->code),
            'metrics' => [
                'clicks' => $participant->hasMany(ReferralClick::class, 'participant_id')->count(),
                'registrations' => $participant->attributions->where('status', '!=', 'rejected')->count(),
                'paid_clients' => $participant->attributions->whereNotNull('first_paid_at')->count(),
                'pending' => $commissions->where('status', 'pending')->sum('amount_minor'),
                'available' => $commissions->where('status', 'available')->sum('amount_minor'),
                'paid' => $commissions->where('status', 'paid')->sum('amount_minor'),
            ],
        ])->withHeaders([
            'Cache-Control' => 'no-store, private',
            'Pragma' => 'no-cache',
            'Referrer-Policy' => 'no-referrer',
            'X-Robots-Tag' => 'noindex, nofollow',
        ]);
    }

    public function acceptOffer(Request $request, ReferralParticipant $participant): RedirectResponse
    {
        $request->validate(['accepted' => ['accepted']]);
        $settings = ReferralProgramSetting::current();
        abort_if(blank($settings->offer_version), 422, 'Версия оферты не опубликована.');

        $participant->forceFill([
            'offer_version' => $settings->offer_version,
            'offer_accepted_at' => now(),
        ])->save();

        return back()->with('status', 'Оферта принята.');
    }

    public function bankDetails(Request $request, ReferralParticipant $participant): RedirectResponse
    {
        $details = $request->validate([
            'recipient' => ['required', 'string', 'max:255'],
            'account' => ['required', 'digits:20'],
            'bank_name' => ['required', 'string', 'max:255'],
            'bik' => ['required', 'digits:9'],
            'correspondent_account' => ['required', 'digits:20'],
        ]);
        $participant->forceFill(['bank_details' => $details, 'bank_details_verified_at' => null])->save();

        return back()->with('status', 'Реквизиты сохранены и ожидают проверки администратора.');
    }

    public function placement(Request $request, ReferralParticipant $participant): RedirectResponse
    {
        abort_unless(ReferralProgramSetting::current()->public_placements_enabled, 404);
        abort_unless($participant->status === 'active' && $participant->suspended_at === null, 403);
        $data = $request->validate([
            'platform' => ['required', 'string', 'max:255'],
            'placement_url' => ['required', 'url:http,https', 'max:2048'],
            'creative_text' => ['required', 'string', 'max:10000'],
        ]);
        $participant->placements()->create($data + ['status' => 'draft']);

        return back()->with('status', 'Заявка создана. Ссылка станет активной после регистрации ERID администратором.');
    }
}
