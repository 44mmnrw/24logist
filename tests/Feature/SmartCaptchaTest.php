<?php

namespace Tests\Feature;

use App\Filament\Clusters\Landing\Resources\SiteSettings\Pages\EditGeneralSiteSetting;
use App\Models\CmsPage;
use App\Models\LandingLead;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\SiteSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SmartCaptchaTest extends TestCase
{
    use RefreshDatabase;

    private const SERVER_KEY = 'test-private-smartcaptcha-key';

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Mail::fake();
        Http::preventStrayRequests();
        $this->configure();
    }

    private function configure(array $overrides = []): void
    {
        SiteSetting::instance()->update(array_merge([
            'smartcaptcha_commercial_offer_enabled' => true,
            'smartcaptcha_contact_enabled' => true,
            'smartcaptcha_site_key' => 'public-smartcaptcha-key',
            'smartcaptcha_server_key' => self::SERVER_KEY,
        ], $overrides));
        app(SiteSettingsService::class)->clearCache();
    }

    public static function forms(): array
    {
        return [
            'commercial offer' => ['commercial_offer', 'leads.commercial-offer.store'],
            'contact' => ['contact', 'leads.contact.store'],
        ];
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Тестовый клиент',
            'company' => 'ООО Тест',
            'inn' => '7707083893',
            'email' => 'test@example.test',
            'phone' => '+7 (900) 000-00-00',
            'privacy_accepted' => true,
            'users' => 1,
            'smart_token' => 'test-one-time-token',
        ], $overrides);
    }

    #[DataProvider('forms')]
    public function test_enabled_form_rejects_missing_token_without_contacting_yandex(string $form, string $route): void
    {
        Http::fake();
        $this->postJson(route($route), $this->payload(['smart_token' => '']))
            ->assertUnprocessable()->assertJsonValidationErrors('smart_token');
        Http::assertNothingSent();
        $this->assertDatabaseCount('landing_leads', 0);
        Mail::assertNothingSent();
    }

    #[DataProvider('forms')]
    public function test_valid_token_is_checked_server_side_before_saving(string $form, string $route): void
    {
        $host = parse_url(route($route), PHP_URL_HOST);
        Http::fake(['smartcaptcha.cloud.yandex.ru/validate' => Http::response(['status' => 'ok', 'host' => $host])]);
        $this->postJson(route($route), $this->payload())->assertCreated();
        Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
            && $request->url() === 'https://smartcaptcha.cloud.yandex.ru/validate'
            && $request->hasHeader('Content-Type', 'application/x-www-form-urlencoded')
            && $request['secret'] === self::SERVER_KEY
            && $request['token'] === 'test-one-time-token'
            && filled($request['ip']));
        Http::assertSentCount(1);
        $this->assertDatabaseCount('landing_leads', 1);
        $this->assertStringNotContainsString('test-one-time-token', LandingLead::query()->sole()->toJson());
    }

    public static function rejectedResponses(): array
    {
        $responses = [
            'invalid or expired' => [['status' => 'failed', 'message' => 'Invalid or expired Token.'], 200],
            'other domain' => [['status' => 'ok', 'host' => 'other.example'], 200],
            'empty host' => [['status' => 'ok', 'host' => ''], 200],
            'malformed' => ['not-json', 200],
            'service error' => [[], 503],
        ];
        $cases = [];
        foreach (self::forms() as $name => [$form, $route]) {
            foreach ($responses as $reason => [$body, $status]) {
                $cases[$name.' / '.$reason] = [$route, $body, $status];
            }
        }

        return $cases;
    }

    #[DataProvider('rejectedResponses')]
    public function test_failed_verification_never_creates_a_lead(string $route, array|string $body, int $status): void
    {
        Http::fake(['smartcaptcha.cloud.yandex.ru/validate' => Http::response($body, $status)]);
        $this->postJson(route($route), $this->payload())
            ->assertUnprocessable()->assertJsonValidationErrors('smart_token');
        $this->assertDatabaseCount('landing_leads', 0);
        Mail::assertNothingSent();
    }

    #[DataProvider('forms')]
    public function test_connection_failure_does_not_bypass_captcha(string $form, string $route): void
    {
        Http::fake(['*' => Http::failedConnection()]);
        $this->postJson(route($route), $this->payload())
            ->assertUnprocessable()->assertJsonValidationErrors('smart_token');
        $this->assertDatabaseCount('landing_leads', 0);
    }

    #[DataProvider('forms')]
    public function test_each_form_can_be_disabled_independently(string $form, string $route): void
    {
        $this->configure(['smartcaptcha_'.$form.'_enabled' => false]);
        Http::fake();
        $this->postJson(route($route), $this->payload(['smart_token' => '']))->assertCreated();
        Http::assertNothingSent();
        $html = view('components.site.smartcaptcha', ['form' => $form])->render();
        $this->assertStringNotContainsString('data-smartcaptcha', $html);
    }

    #[DataProvider('forms')]
    public function test_missing_server_key_does_not_silently_disable_protection(string $form, string $route): void
    {
        $this->configure(['smartcaptcha_server_key' => null]);
        Http::fake();
        $this->postJson(route($route), $this->payload())
            ->assertUnprocessable()->assertJsonValidationErrors('smart_token');
        Http::assertNothingSent();
        $this->assertDatabaseCount('landing_leads', 0);
    }

    public function test_only_public_key_is_rendered_and_secret_is_encrypted(): void
    {
        $settings = SiteSetting::instance();
        $this->assertNotSame(self::SERVER_KEY, $settings->getRawOriginal('smartcaptcha_server_key'));
        $this->assertSame(self::SERVER_KEY, $settings->smartcaptcha_server_key);
        $this->assertArrayNotHasKey('smartcaptcha_server_key', $settings->toArray());
        foreach (['commercial_offer', 'contact'] as $form) {
            $html = view('components.site.smartcaptcha', ['form' => $form])->render();
            $this->assertStringContainsString('public-smartcaptcha-key', $html);
            $this->assertStringNotContainsString(self::SERVER_KEY, $html);
        }
        $this->get('/')->assertOk()->assertSee('data-smartcaptcha', false)->assertDontSee(self::SERVER_KEY);
        CmsPage::query()->create(['slug' => 'contacts', 'title' => 'Контакты', 'is_published' => true]);
        $this->get('/pages/contacts')->assertOk()->assertSee('data-smartcaptcha', false)->assertDontSee(self::SERVER_KEY);
    }

    public function test_admin_can_save_settings_and_blank_secret_preserves_existing_key(): void
    {
        $this->actingAs(User::factory()->create());
        Livewire::test(EditGeneralSiteSetting::class)
            ->assertSet('data.smartcaptcha_server_key', '')
            ->set('data.smartcaptcha_site_key', 'updated-public-key')
            ->set('data.smartcaptcha_contact_enabled', false)
            ->call('save')->assertHasNoFormErrors();
        $settings = SiteSetting::instance();
        $this->assertSame(self::SERVER_KEY, $settings->smartcaptcha_server_key);
        $this->assertSame('updated-public-key', app(SiteSettingsService::class)->get()->smartcaptcha_site_key);
        $this->assertFalse($settings->smartcaptcha_contact_enabled);

        Livewire::test(EditGeneralSiteSetting::class)
            ->set('data.smartcaptcha_server_key', 'replacement-server-key')
            ->call('save')->assertHasNoFormErrors()
            ->assertSet('data.smartcaptcha_server_key', '');
        $this->assertSame('replacement-server-key', SiteSetting::instance()->smartcaptcha_server_key);
    }

    public function test_admin_cannot_enable_captcha_without_keys(): void
    {
        $this->configure(['smartcaptcha_site_key' => null, 'smartcaptcha_server_key' => null]);
        $this->actingAs(User::factory()->create());
        Livewire::test(EditGeneralSiteSetting::class)->call('save')
            ->assertHasFormErrors(['smartcaptcha_site_key' => 'required', 'smartcaptcha_server_key' => 'required']);
    }
}
