<?php

namespace Tests\Feature;

use App\Models\ReferralAttribution;
use App\Models\ReferralCommission;
use App\Models\ReferralParticipant;
use App\Models\ReferralPlacement;
use App\Models\ReferralProgramSetting;
use App\Models\ReferralTerm;
use App\Models\User;
use App\Services\Referral\ReferralCommissionService;
use App\Services\Referral\ReferralPayoutService;
use App\Services\Referral\ReferralProgramService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class ReferralProgramTest extends TestCase
{
    use RefreshDatabase;

    private ReferralTerm $terms;

    private ReferralParticipant $participant;

    protected function setUp(): void
    {
        parent::setUp();
        ReferralProgramSetting::current()->update(['is_enabled' => true, 'attribution_days' => 90]);
        $this->terms = ReferralTerm::query()->create([
            'name' => 'Пилот', 'status' => 'active', 'commission_bps' => 1500,
            'commission_months' => 12, 'invitee_discount_bps' => 700,
            'hold_days' => 30, 'minimum_payout_minor' => 500000,
            'starts_at' => now()->subDay(), 'ends_at' => now()->addYear(), 'published_at' => now(),
        ]);
        $this->participant = ReferralParticipant::query()->create([
            'terms_id' => $this->terms->id, 'external_account_id' => 'account-referrer',
            'company_name' => 'ООО Рекомендатель', 'inn' => '7701000001',
            'contact_email' => 'partner@example.test', 'status' => 'active',
            'offer_version' => '1', 'offer_accepted_at' => now(), 'bank_details_verified_at' => now(),
        ]);
    }

    public function test_first_touch_cookie_is_secure_and_is_not_overwritten(): void
    {
        $first = $this->get('/r/'.$this->participant->code);
        $first->assertRedirect('/')->assertCookie(config('referrals.cookie_name'));
        $cookie = collect($first->headers->getCookies())
            ->first(fn ($item) => $item->getName() === config('referrals.cookie_name'));
        $this->assertNotNull($cookie);
        $this->assertTrue($cookie->isSecure());
        $this->assertTrue($cookie->isHttpOnly());
        $this->assertSame('lax', strtolower((string) $cookie->getSameSite()));

        $other = ReferralParticipant::query()->create([
            'terms_id' => $this->terms->id, 'external_account_id' => 'other', 'company_name' => 'ООО Другой',
            'inn' => '7701000002', 'contact_email' => 'other@example.test', 'status' => 'active',
            'offer_accepted_at' => now(), 'bank_details_verified_at' => now(),
        ]);
        $this->withCookie(config('referrals.cookie_name'), $this->participant->code)
            ->get('/r/'.$other->code)->assertRedirect('/')->assertCookieMissing(config('referrals.cookie_name'));
    }

    public function test_registration_fixes_terms_and_later_changes_do_not_recalculate_snapshot(): void
    {
        $attribution = app(ReferralProgramService::class)->captureRegistration([
            'referral_code' => $this->participant->code,
            'external_registration_id' => 'reg-1', 'external_account_id' => 'account-new',
            'company_name' => 'ООО Клиент', 'inn' => '7701000010', 'email' => 'client@example.test',
        ]);
        $this->assertSame(1500, $attribution->commission_bps);
        $this->assertSame(700, $attribution->invitee_discount_bps);

        $this->terms->update(['commission_bps' => 2500, 'invitee_discount_bps' => 1000]);
        $this->assertSame(1500, $attribution->fresh()->commission_bps);
        $this->assertSame(700, $attribution->fresh()->invitee_discount_bps);
    }

    public function test_self_referral_is_rejected_without_blocking_capture(): void
    {
        $attribution = app(ReferralProgramService::class)->captureRegistration([
            'referral_code' => $this->participant->code,
            'external_registration_id' => 'reg-self', 'company_name' => 'ООО Рекомендатель',
            'inn' => $this->participant->inn,
        ]);
        $this->assertSame('rejected', $attribution->status);
        $this->assertNotNull($attribution->rejection_reason);
    }

    public function test_public_link_requires_erid(): void
    {
        ReferralProgramSetting::current()->update(['public_placements_enabled' => true]);
        $placement = ReferralPlacement::query()->create([
            'participant_id' => $this->participant->id, 'status' => 'draft', 'platform' => 'Блог',
            'placement_url' => 'https://example.test/post', 'creative_text' => 'Реклама',
        ]);
        $this->assertNull(app(ReferralProgramService::class)->resolveCode($placement->code));

        $placement->update(['status' => 'active', 'erid' => '2VtzqvTest']);
        $this->assertSame('public', app(ReferralProgramService::class)->resolveCode($placement->code)['source_type']);
    }

    public function test_payment_is_idempotent_uses_net_of_vat_and_refund_creates_adjustment(): void
    {
        app(ReferralProgramService::class)->captureRegistration([
            'referral_code' => $this->participant->code, 'external_registration_id' => 'reg-pay',
            'external_account_id' => 'account-pay', 'company_name' => 'ООО Плательщик', 'inn' => '7701000020',
        ]);
        $service = app(ReferralCommissionService::class);
        $event = ['event_id' => 'evt-1', 'external_payment_id' => 'pay-1', 'external_account_id' => 'account-pay', 'amount_minor' => 120000, 'vat_minor' => 20000];
        $first = $service->recordPayment($event);
        $second = $service->recordPayment($event);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(100000, $first->base_minor);
        $this->assertSame(15000, $first->amount_minor);
        $this->assertDatabaseCount('referral_commissions', 1);

        $adjustment = $service->recordRefund(['event_id' => 'refund-1', 'external_payment_id' => 'pay-1', 'refund_base_minor' => 40000]);
        $this->assertSame(-6000, $adjustment->amount_minor);
        $this->assertSame('refund_adjustment', $adjustment->type);
    }

    public function test_monthly_registry_respects_snapshot_threshold(): void
    {
        $attribution = ReferralAttribution::query()->create([
            'participant_id' => $this->participant->id, 'terms_id' => $this->terms->id,
            'external_account_id' => 'threshold-account', 'source_type' => 'personal',
            'referral_code' => $this->participant->code, 'referred_inn' => '7701000030', 'status' => 'active',
            'commission_bps' => 1500, 'commission_months' => 12, 'invitee_discount_bps' => 700,
            'hold_days' => 30, 'minimum_payout_minor' => 500000, 'attributed_at' => now(),
        ]);
        ReferralCommission::query()->create([
            'attribution_id' => $attribution->id, 'event_key' => 'threshold-1', 'status' => 'available',
            'base_minor' => 1000000, 'rate_bps' => 1500, 'amount_minor' => 150000, 'available_at' => now(),
        ]);
        $this->assertCount(0, app(ReferralPayoutService::class)->createMonthlyRegistry());

        ReferralCommission::query()->create([
            'attribution_id' => $attribution->id, 'event_key' => 'threshold-2', 'status' => 'available',
            'base_minor' => 3000000, 'rate_bps' => 1500, 'amount_minor' => 450000, 'available_at' => now(),
        ]);
        $this->assertCount(1, app(ReferralPayoutService::class)->createMonthlyRegistry());
    }

    public function test_platform_api_requires_secret(): void
    {
        config(['referrals.platform_api_secret' => 'test-secret']);
        $this->postJson('/api/v1/referrals/payments', [])->assertUnauthorized();
        $this->withToken('test-secret')->postJson('/api/v1/referrals/payments', [])->assertUnprocessable();
    }

    public function test_partner_portal_requires_a_valid_signed_link(): void
    {
        $this->get('/referral-program/'.$this->participant->id)->assertForbidden();
        $url = URL::signedRoute('referrals.portal.show', ['participant' => $this->participant->id]);
        $this->get($url)->assertOk()->assertHeader('Cache-Control', 'no-store, private')
            ->assertSee('Реферальная программа')->assertDontSee('portal_token');
    }

    public function test_referral_admin_pages_are_available_to_admin(): void
    {
        $admin = User::factory()->create();
        $this->actingAs($admin)->get('/admin/referral-settings')->assertOk();
        $this->actingAs($admin)->get('/admin/referral-terms')->assertOk();
        $this->actingAs($admin)->get('/admin/referral-participants')->assertOk();
        $this->actingAs($admin)->get('/admin/referral-partner-applications')->assertOk();
        $this->actingAs($admin)->get('/admin/referrals/payouts/export.csv')->assertOk();
        $this->actingAs($admin)->get('/admin/referrals/placements/export.csv')->assertOk();
        if (class_exists(\ZipArchive::class)) {
            $this->actingAs($admin)->get('/admin/referrals/payouts/export.xlsx')->assertOk();
        }
    }

    public function test_company_can_submit_partner_application_without_becoming_active_automatically(): void
    {
        $this->get('/partners/register')->assertOk()->assertSee('Стать партнёром');

        $this->post('/partners/register', [
            'company_name' => 'ООО Новый партнёр',
            'inn' => '7701000099',
            'contact_name' => 'Иван Иванов',
            'contact_email' => 'new-partner@example.test',
            'contact_phone' => '+7 999 111-22-33',
            'terms_accepted' => '1',
            'privacy_accepted' => '1',
        ])->assertRedirect(route('referrals.partners.registered'));

        $this->assertDatabaseHas('referral_partner_applications', [
            'inn' => '7701000099',
            'status' => 'pending',
        ]);
        $this->assertDatabaseMissing('referral_participants', ['inn' => '7701000099']);
    }
}
