<?php

namespace App\Services\Referral;

use App\Models\ReferralCommission;
use App\Models\ReferralParticipant;
use App\Models\ReferralPayout;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class ReferralPayoutService
{
    /** @return Collection<int, ReferralPayout> */
    public function createMonthlyRegistry(?\DateTimeInterface $periodEnd = null): Collection
    {
        $periodEnd ??= now()->subMonthNoOverflow()->endOfMonth();
        $periodStart = Carbon::parse($periodEnd)->startOfMonth();
        $created = collect();

        ReferralParticipant::query()->where('status', 'active')->each(function (ReferralParticipant $participant) use ($periodStart, $periodEnd, $created): void {
            DB::transaction(function () use ($participant, $periodStart, $periodEnd, $created): void {
                $entries = ReferralCommission::query()
                    ->whereHas('attribution', fn ($query) => $query->where('participant_id', $participant->id))
                    ->where('status', 'available')
                    ->whereNull('payout_id')
                    ->lockForUpdate()
                    ->get();
                if ($entries->isEmpty()) {
                    return;
                }

                $amount = (int) $entries->sum('amount_minor');
                $threshold = (int) ($entries->max(fn (ReferralCommission $entry) => $entry->attribution->minimum_payout_minor) ?? PHP_INT_MAX);
                if ($amount < $threshold || $amount <= 0) {
                    return;
                }

                $payout = ReferralPayout::query()->create([
                    'participant_id' => $participant->id,
                    'period_start' => $periodStart,
                    'period_end' => $periodEnd,
                    'amount_minor' => $amount,
                    'currency' => 'RUB',
                    'status' => 'draft',
                ]);
                ReferralCommission::query()->whereKey($entries->modelKeys())->update([
                    'payout_id' => $payout->id,
                    'status' => 'in_payout',
                    'updated_at' => now(),
                ]);
                $created->push($payout);
            });
        });

        return $created;
    }

    public function markPaid(ReferralPayout $payout, string $paymentReference, ?int $actorId = null): void
    {
        if ($payout->status !== 'approved') {
            throw ValidationException::withMessages(['status' => 'Сначала утвердите реестр выплат.']);
        }
        DB::transaction(function () use ($payout, $paymentReference, $actorId): void {
            $payout->forceFill([
                'status' => 'paid',
                'payment_reference' => $paymentReference,
                'approved_by_user_id' => $actorId,
                'approved_at' => $payout->approved_at,
                'paid_at' => now(),
            ])->save();
            $payout->commissions()->update(['status' => 'paid', 'paid_at' => now(), 'updated_at' => now()]);
        });
    }
}
