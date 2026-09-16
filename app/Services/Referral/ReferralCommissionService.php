<?php

namespace App\Services\Referral;

use App\Models\ReferralAttribution;
use App\Models\ReferralCommission;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class ReferralCommissionService
{
    /**
     * @param  array{event_id:string,external_payment_id:string,external_account_id:string,amount_minor:int,vat_minor?:int,discount_minor?:int,paid_at?:string,is_test?:bool,currency?:string}  $event
     */
    public function recordPayment(array $event): ?ReferralCommission
    {
        if (($event['is_test'] ?? false) || (int) $event['amount_minor'] <= 0) {
            return null;
        }

        $attribution = ReferralAttribution::query()
            ->where('external_account_id', $event['external_account_id'])
            ->whereIn('status', ['confirmed', 'active'])
            ->first();
        if ($attribution === null) {
            return null;
        }

        $paidAt = isset($event['paid_at']) ? Carbon::parse($event['paid_at']) : now();
        if ($attribution->commission_ends_at?->lt($paidAt)) {
            return null;
        }

        $baseMinor = max(0, (int) $event['amount_minor'] - max(0, (int) ($event['vat_minor'] ?? 0)));
        if ($baseMinor === 0) {
            return null;
        }

        return DB::transaction(function () use ($event, $attribution, $paidAt, $baseMinor): ReferralCommission {
            $locked = ReferralAttribution::query()->lockForUpdate()->findOrFail($attribution->id);
            if ($locked->first_paid_at === null) {
                $locked->first_paid_at = $paidAt;
                $locked->commission_ends_at = $paidAt->copy()->addMonthsNoOverflow($locked->commission_months);
                $locked->discount_used_at = $paidAt;
            }
            $locked->status = 'active';
            $locked->save();

            return ReferralCommission::query()->firstOrCreate(
                ['event_key' => 'payment:'.$event['event_id']],
                [
                    'attribution_id' => $locked->id,
                    'external_payment_id' => $event['external_payment_id'],
                    'type' => 'commission',
                    'status' => 'pending',
                    'base_minor' => $baseMinor,
                    'rate_bps' => $locked->commission_bps,
                    'amount_minor' => intdiv($baseMinor * $locked->commission_bps + 5000, 10000),
                    'available_at' => $paidAt->copy()->addDays($locked->hold_days),
                    'metadata' => ['currency' => $event['currency'] ?? 'RUB'],
                ],
            );
        });
    }

    /** @param array{event_id:string,external_payment_id:string,refund_base_minor:int,refunded_at?:string} $event */
    public function recordRefund(array $event): ?ReferralCommission
    {
        $original = ReferralCommission::query()
            ->where('external_payment_id', $event['external_payment_id'])
            ->where('type', 'commission')
            ->first();
        if ($original === null || (int) $event['refund_base_minor'] <= 0) {
            return null;
        }

        $refundedAt = isset($event['refunded_at']) ? Carbon::parse($event['refunded_at']) : now();
        $amount = intdiv((int) $event['refund_base_minor'] * $original->rate_bps + 5000, 10000);

        return ReferralCommission::query()->firstOrCreate(
            ['event_key' => 'refund:'.$event['event_id']],
            [
                'attribution_id' => $original->attribution_id,
                'external_payment_id' => $event['external_payment_id'],
                'type' => 'refund_adjustment',
                'status' => $original->status === 'paid' ? 'available' : 'pending',
                'base_minor' => (int) $event['refund_base_minor'],
                'rate_bps' => $original->rate_bps,
                'amount_minor' => -$amount,
                'available_at' => $original->status === 'paid' ? $refundedAt : $original->available_at,
                'metadata' => ['original_commission_id' => $original->id],
            ],
        );
    }

    public function releaseDue(): int
    {
        return ReferralCommission::query()
            ->where('status', 'pending')
            ->where('available_at', '<=', now())
            ->update(['status' => 'available', 'updated_at' => now()]);
    }
}
