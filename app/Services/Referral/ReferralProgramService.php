<?php

namespace App\Services\Referral;

use App\Models\ReferralAttribution;
use App\Models\ReferralClick;
use App\Models\ReferralParticipant;
use App\Models\ReferralPlacement;
use App\Models\ReferralProgramSetting;
use App\Models\ReferralTerm;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

final class ReferralProgramService
{
    /** @return array{participant:ReferralParticipant,placement:?ReferralPlacement,source_type:string}|null */
    public function resolveCode(string $code): ?array
    {
        $code = trim($code);
        $settings = ReferralProgramSetting::current();
        if (! $settings->is_enabled || $code === '') {
            return null;
        }

        $participant = ReferralParticipant::query()->where('code', $code)->first();
        if ($participant?->isEligible()) {
            return ['participant' => $participant, 'placement' => null, 'source_type' => 'personal'];
        }

        if (! $settings->public_placements_enabled) {
            return null;
        }

        $placement = ReferralPlacement::query()
            ->with('participant')
            ->where('code', $code)
            ->where('status', 'active')
            ->whereNotNull('erid')
            ->where(fn ($query) => $query->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>=', now()))
            ->first();

        if (! $placement?->participant?->isEligible()) {
            return null;
        }

        return ['participant' => $placement->participant, 'placement' => $placement, 'source_type' => 'public'];
    }

    public function recordClick(string $code, ?string $ip, ?string $userAgent): bool
    {
        $resolved = $this->resolveCode($code);
        if ($resolved === null) {
            return false;
        }

        ReferralClick::query()->create([
            'participant_id' => $resolved['participant']->id,
            'placement_id' => $resolved['placement']?->id,
            'referral_code' => $code,
            'ip_hash' => filled($ip) ? hash_hmac('sha256', (string) $ip, (string) config('app.key')) : null,
            'user_agent_hash' => filled($userAgent) ? hash_hmac('sha256', (string) $userAgent, (string) config('app.key')) : null,
            'visited_at' => now(),
        ]);

        return true;
    }

    /**
     * Fixes referral terms at the first successful registration. Invalid or fraudulent
     * referrals never block the registration itself.
     *
     * @param  array{referral_code?:string,external_registration_id?:string,external_account_id?:string,company_name?:string,inn:string,email?:string,existed_before?:bool,previously_paid?:bool,matching_bank_details?:bool,matching_owner?:bool,matching_phone?:bool,anomalous_activity?:bool,fraud_signals?:array<int,string>}  $data
     */
    public function captureRegistration(array $data): ?ReferralAttribution
    {
        $inn = preg_replace('/\D+/', '', (string) $data['inn']) ?? '';
        $existing = ReferralAttribution::query()->where('referred_inn', $inn)->first();
        if ($existing !== null) {
            $updates = [];
            if ($existing->external_registration_id === null && filled($data['external_registration_id'] ?? null)) {
                $updates['external_registration_id'] = $data['external_registration_id'];
            }
            if ($existing->external_account_id === null && filled($data['external_account_id'] ?? null)) {
                $updates['external_account_id'] = $data['external_account_id'];
            }
            if ($existing->status !== 'rejected' && (($data['existed_before'] ?? false) || ($data['previously_paid'] ?? false))) {
                $updates['status'] = 'rejected';
                $updates['rejection_reason'] = 'Компания существовала или платила до реферального перехода.';
            } elseif ($existing->status === 'confirmed' && $this->requiresReview($data)) {
                $updates['status'] = 'review';
                $updates['rejection_reason'] = $this->reviewReason($data);
            }
            if ($updates !== []) {
                $existing->forceFill($updates)->save();
            }

            return $existing;
        }

        $resolved = $this->resolveCode((string) ($data['referral_code'] ?? ''));
        if ($resolved === null) {
            return null;
        }

        $participant = $resolved['participant'];
        $terms = $participant->terms;
        if (! $terms?->isActiveAt()) {
            $terms = ReferralTerm::query()
                ->where('status', 'active')
                ->where(fn ($query) => $query->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
                ->where(fn ($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>=', now()))
                ->latest('published_at')
                ->first();
        }
        if ($terms === null) {
            return null;
        }

        $rejection = null;
        if ($inn === $participant->inn) {
            $rejection = 'Самореферал по ИНН.';
        } elseif (($data['existed_before'] ?? false) || ($data['previously_paid'] ?? false)) {
            $rejection = 'Компания существовала или платила до реферального перехода.';
        }
        $requiresReview = $rejection === null && $this->requiresReview($data);

        $snapshot = $participant->effectiveTerms($terms);

        try {
            return DB::transaction(function () use ($data, $resolved, $participant, $terms, $inn, $rejection, $requiresReview, $snapshot): ReferralAttribution {
                return ReferralAttribution::query()->create(array_merge($snapshot, [
                    'participant_id' => $participant->id,
                    'placement_id' => $resolved['placement']?->id,
                    'terms_id' => $terms->id,
                    'external_registration_id' => $data['external_registration_id'] ?? null,
                    'external_account_id' => $data['external_account_id'] ?? null,
                    'source_type' => $resolved['source_type'],
                    'referral_code' => (string) $data['referral_code'],
                    'referred_company_name' => $data['company_name'] ?? null,
                    'referred_inn' => $inn,
                    'referred_email' => isset($data['email']) ? mb_strtolower(trim((string) $data['email'])) : null,
                    'status' => $rejection !== null ? 'rejected' : ($requiresReview ? 'review' : 'confirmed'),
                    'rejection_reason' => $rejection ?? ($requiresReview ? $this->reviewReason($data) : null),
                    'attributed_at' => now(),
                    'confirmed_at' => $rejection === null && ! $requiresReview ? now() : null,
                ]));
            });
        } catch (QueryException) {
            return ReferralAttribution::query()->where('referred_inn', $inn)->first();
        }
    }

    public function confirmAccount(string $registrationId, string $accountId): ?ReferralAttribution
    {
        $attribution = ReferralAttribution::query()->where('external_registration_id', $registrationId)->first();
        if ($attribution === null || $attribution->status === 'rejected') {
            return $attribution;
        }

        $values = ['external_account_id' => $accountId];
        if ($attribution->status !== 'review') {
            $values['status'] = 'confirmed';
            $values['confirmed_at'] = $attribution->confirmed_at ?? now();
        }
        $attribution->forceFill($values)->save();

        return $attribution;
    }

    public function inviteeDiscountBps(string $accountId): int
    {
        return (int) (ReferralAttribution::query()
            ->where('external_account_id', $accountId)
            ->where('status', 'confirmed')
            ->whereNull('discount_used_at')
            ->value('invitee_discount_bps') ?? 0);
    }

    /** @param array<string,mixed> $data */
    private function requiresReview(array $data): bool
    {
        return (bool) ($data['matching_bank_details'] ?? false)
            || (bool) ($data['matching_owner'] ?? false)
            || (bool) ($data['matching_phone'] ?? false)
            || (bool) ($data['anomalous_activity'] ?? false)
            || ($data['fraud_signals'] ?? []) !== [];
    }

    /** @param array<string,mixed> $data */
    private function reviewReason(array $data): string
    {
        $signals = array_values(array_filter([
            ($data['matching_bank_details'] ?? false) ? 'совпадают платёжные реквизиты' : null,
            ($data['matching_owner'] ?? false) ? 'совпадает владелец' : null,
            ($data['matching_phone'] ?? false) ? 'совпадает телефон' : null,
            ($data['anomalous_activity'] ?? false) ? 'аномальная активность' : null,
            ...array_map(static fn ($value): string => (string) $value, (array) ($data['fraud_signals'] ?? [])),
        ]));

        return 'Требуется проверка: '.implode(', ', $signals).'.';
    }
}
