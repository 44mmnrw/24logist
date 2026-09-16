<?php

namespace App\Http\Controllers;

use App\Services\Referral\ReferralCommissionService;
use App\Services\Referral\ReferralProgramService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ReferralApiController extends Controller
{
    public function __construct(
        private readonly ReferralProgramService $program,
        private readonly ReferralCommissionService $commissions,
    ) {}

    public function registration(Request $request): JsonResponse
    {
        $data = $request->validate([
            'referral_code' => ['nullable', 'string', 'max:32', 'regex:/^[A-Za-z0-9_-]+$/'],
            'external_registration_id' => ['required', 'string', 'max:191'],
            'external_account_id' => ['nullable', 'string', 'max:191'],
            'company_name' => ['required', 'string', 'max:255'],
            'inn' => ['required', 'string', 'regex:/^\d{10}(\d{2})?$/'],
            'email' => ['nullable', 'email', 'max:255'],
            'existed_before' => ['sometimes', 'boolean'],
            'previously_paid' => ['sometimes', 'boolean'],
            'matching_bank_details' => ['sometimes', 'boolean'],
            'matching_owner' => ['sometimes', 'boolean'],
            'matching_phone' => ['sometimes', 'boolean'],
            'anomalous_activity' => ['sometimes', 'boolean'],
            'fraud_signals' => ['sometimes', 'array', 'max:20'],
            'fraud_signals.*' => ['string', 'max:255'],
        ]);

        $attribution = $this->program->captureRegistration($data);

        return response()->json([
            'attributed' => $attribution !== null && $attribution->status !== 'rejected',
            'status' => $attribution?->status,
            'discount_bps' => in_array($attribution?->status, ['confirmed', 'active'], true) ? $attribution->invitee_discount_bps : 0,
        ]);
    }

    public function confirm(Request $request): JsonResponse
    {
        $data = $request->validate([
            'external_registration_id' => ['required', 'string', 'max:191'],
            'external_account_id' => ['required', 'string', 'max:191'],
        ]);
        $attribution = $this->program->confirmAccount($data['external_registration_id'], $data['external_account_id']);

        return response()->json(['confirmed' => $attribution !== null && $attribution->status !== 'rejected']);
    }

    public function discount(string $accountId): JsonResponse
    {
        return response()->json(['discount_bps' => $this->program->inviteeDiscountBps($accountId)]);
    }

    public function payment(Request $request): JsonResponse
    {
        $event = $request->validate([
            'event_id' => ['required', 'string', 'max:191'],
            'external_payment_id' => ['required', 'string', 'max:191'],
            'external_account_id' => ['required', 'string', 'max:191'],
            'amount_minor' => ['required', 'integer', 'min:1'],
            'vat_minor' => ['sometimes', 'integer', 'min:0'],
            'discount_minor' => ['sometimes', 'integer', 'min:0'],
            'paid_at' => ['sometimes', 'date'],
            'is_test' => ['sometimes', 'boolean'],
            'currency' => ['sometimes', 'string', 'size:3'],
        ]);
        $commission = $this->commissions->recordPayment($event);

        return response()->json(['accepted' => true, 'commission_id' => $commission?->id]);
    }

    public function refund(Request $request): JsonResponse
    {
        $event = $request->validate([
            'event_id' => ['required', 'string', 'max:191'],
            'external_payment_id' => ['required', 'string', 'max:191'],
            'refund_base_minor' => ['required', 'integer', 'min:1'],
            'refunded_at' => ['sometimes', 'date'],
        ]);
        $adjustment = $this->commissions->recordRefund($event);

        return response()->json(['accepted' => true, 'adjustment_id' => $adjustment?->id]);
    }
}
