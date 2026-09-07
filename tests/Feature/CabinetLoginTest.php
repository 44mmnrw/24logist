<?php

namespace Tests\Feature;

use App\Models\LandingBlock;
use App\Models\LandingSection;
use App\Models\SiteSetting;
use App\Services\LandingPageService;
use App\Services\PublicPageCache;
use App\Services\SiteSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class CabinetLoginTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'cabinet-login-secret-at-least-32-characters';

    private const TICKET = 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();

        LandingSection::query()->create([
            'slug' => 'header',
            'name' => 'Шапка',
            'is_active' => true,
        ]);

        SiteSetting::instance()->update([
            'cabinet_login_enabled' => true,
            'cabinet_registration_enabled' => true,
            'cabinet_login_button_text' => 'Войти в личный кабинет',
            'cabinet_registration_button_text' => 'Создать личный кабинет',
            'cabinet_login_modal_title' => 'Вход в платформу',
            'cabinet_login_modal_description' => 'Введите email и пароль.',
            'cabinet_login_identifier_label' => 'Email',
            'cabinet_login_password_label' => 'Пароль',
            'cabinet_login_submit_text' => 'Войти',
            'cabinet_login_url' => 'https://platform.example.test',
            'cabinet_login_forgot_url' => 'https://platform.example.test/password/reset',
            'cabinet_login_origin' => 'https://landing.example.test',
            'cabinet_login_connect_ip' => '127.0.0.1',
            'cabinet_login_api_secret' => self::SECRET,
            'cabinet_login_api_timeout' => 12,
        ]);

        app(SiteSettingsService::class)->clearCache();
        app(LandingPageService::class)->clearCache();
        app(PublicPageCache::class)->forgetLanding();
    }

    public function test_landing_shows_login_and_registration_without_exposing_server_credentials(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Войти в личный кабинет')
            ->assertSee('Создать личный кабинет')
            ->assertSee('Вход в платформу')
            ->assertSee('data-cabinet-login-open', false)
            ->assertSee('data-cabinet-registration-open', false)
            ->assertSee('data-cabinet-login-modal', false)
            ->assertSee('cabinet-login-modal__brand-logo', false)
            ->assertSee('href="#icon-x"', false)
            ->assertSee('href="#icon-eye"', false)
            ->assertSee('href="#icon-eye-off"', false)
            ->assertSee('data-password-toggle', false)
            ->assertSee('data-cabinet-auth-form="registration"', false)
            ->assertSee('Заполните форму для регистрации в системе. После регистрации проверьте почту.')
            ->assertSee('data-party-account-name', false)
            ->assertSee('name="account_name" autocomplete="organization" required', false)
            ->assertSee('data-party-inn', false)
            ->assertSee('data-phone-mask', false)
            ->assertSee('placeholder="+7 (___) ___-__-__"', false)
            ->assertSee('data-default-text="Создать личный кабинет" disabled', false)
            ->assertSee(route('cabinet.register.party-suggestions'), false)
            ->assertDontSee(self::SECRET)
            ->assertDontSee('127.0.0.1')
            ->assertDontSee('/api/v1/landing-login/tickets')
            ->assertDontSee('/api/v1/landing-registration');

        $this->assertNotSame(
            self::SECRET,
            SiteSetting::instance()->getRawOriginal('cabinet_login_api_secret'),
        );
        $this->assertNotSame(
            '127.0.0.1',
            SiteSetting::instance()->getRawOriginal('cabinet_login_connect_ip'),
        );
    }

    public function test_existing_platform_registration_link_is_replaced_with_modal_button(): void
    {
        LandingBlock::query()->create([
            'section_slug' => 'header',
            'block_type' => 'header_button',
            'title' => 'Создать личный кабинет',
            'link' => 'https://platform.example.test/register',
            'button_style' => 'primary',
            'is_active' => true,
            'sort_order' => 1,
        ]);
        LandingBlock::query()->create([
            'section_slug' => 'header',
            'block_type' => 'header_button',
            'title' => 'Legacy CTA',
            'link' => '/admin/login',
            'button_style' => 'ghost',
            'is_active' => true,
            'sort_order' => 2,
        ]);
        app(LandingPageService::class)->clearCache();

        $this->get('/')
            ->assertOk()
            ->assertSee('data-cabinet-registration-open', false)
            ->assertDontSee('Legacy CTA')
            ->assertDontSee('href="/admin/login"', false)
            ->assertDontSee('href="https://platform.example.test/register"', false);
    }

    public function test_login_is_proxied_with_exact_contract_and_ticket_stays_server_side(): void
    {
        Http::fake([
            'platform.example.test/*' => Http::response([
                'ticket' => self::TICKET,
                'handoff_url' => 'https://platform.example.test/auth/landing/consume',
                'method' => 'POST',
                'expires_in' => 60,
            ]),
        ]);

        $response = $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.10'])
            ->postJson(route('cabinet.login'), [
                'email' => 'User@example.test',
                'password' => 'secret-password',
                'remember' => true,
            ])->assertOk()->assertExactJson([
                'handoff_url' => route('cabinet.handoff'),
                'method' => 'POST',
            ]);

        $response->assertDontSee(self::TICKET);

        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://platform.example.test/api/v1/landing-login/tickets'
            && $request['email'] === 'user@example.test'
            && $request['password'] === 'secret-password'
            && $request['remember'] === true
            && $request['origin'] === 'https://landing.example.test'
            && $request['client_ip'] === '192.0.2.10'
            && $request->hasHeader('Authorization', 'Bearer '.self::SECRET));

        $this->post(route('cabinet.handoff'))
            ->assertOk()
            ->assertSee('action="https://platform.example.test/auth/landing/consume"', false)
            ->assertSee('name="ticket" value="'.self::TICKET.'"', false)
            ->assertSee("document.getElementById('cabinet-platform-handoff').requestSubmit();", false)
            ->assertDontSee('Открываем личный кабинет')
            ->assertHeader('Cache-Control', 'no-store, private');

        $this->post(route('cabinet.handoff'))->assertGone();
    }

    public function test_registration_is_proxied_with_full_platform_contract(): void
    {
        Http::fake([
            'platform.example.test/*' => Http::response($this->handoffPayload(), 201),
        ]);

        $response = $this->withHeader('User-Agent', 'Landing browser')
            ->withServerVariables(['REMOTE_ADDR' => '198.51.100.15'])
            ->postJson(route('cabinet.register'), [
                'name' => 'Иван Петров',
                'account_name' => 'ООО Вектор',
                'inn' => '7707083893',
                'email' => 'OWNER@EXAMPLE.TEST',
                'phone' => '+7 999 111-22-33',
                'password' => 'strong-password',
                'password_confirmation' => 'strong-password',
                'capabilities' => ['forwarder', 'cargo_owner'],
                'terms_accepted' => true,
                'privacy_policy_accepted' => true,
            ])
            ->assertOk()
            ->assertExactJson([
                'handoff_url' => route('cabinet.handoff'),
                'method' => 'POST',
            ]);

        $response->assertDontSee(self::TICKET);

        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://platform.example.test/api/v1/landing-registration'
            && $request['name'] === 'Иван Петров'
            && $request['account_name'] === 'ООО Вектор'
            && $request['inn'] === '7707083893'
            && $request['email'] === 'owner@example.test'
            && $request['phone'] === '+79991112233'
            && $request['password_confirmation'] === 'strong-password'
            && $request['capabilities'] === ['forwarder', 'cargo_owner']
            && $request['terms_accepted'] === true
            && $request['privacy_policy_accepted'] === true
            && $request['origin'] === 'https://landing.example.test'
            && $request['client_ip'] === '198.51.100.15'
            && $request['client_user_agent'] === 'Landing browser'
            && $request->hasHeader('Authorization', 'Bearer '.self::SECRET));
    }

    public function test_registration_requires_account_name_before_contacting_platform(): void
    {
        Http::fake();

        $this->postJson(route('cabinet.register'), [
            'name' => 'Иван Петров',
            'inn' => '7707083893',
            'email' => 'owner@example.test',
            'phone' => '+7 999 111-22-33',
            'password' => 'strong-password',
            'password_confirmation' => 'strong-password',
            'capabilities' => ['forwarder'],
            'terms_accepted' => true,
            'privacy_policy_accepted' => true,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['account_name']);

        Http::assertNothingSent();
    }

    public function test_registration_rejects_incomplete_and_overlong_phone_numbers(): void
    {
        Http::fake();

        $payload = [
            'name' => 'Иван Петров',
            'account_name' => 'ООО Вектор',
            'inn' => '7707083893',
            'email' => 'owner@example.test',
            'password' => 'strong-password',
            'password_confirmation' => 'strong-password',
            'capabilities' => ['forwarder'],
            'terms_accepted' => true,
            'privacy_policy_accepted' => true,
        ];

        foreach (['+7 (999) 111-22-3', '+7 (999) 111-22-334'] as $phone) {
            $this->postJson(route('cabinet.register'), [...$payload, 'phone' => $phone])
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['phone']);
        }

        Http::assertNothingSent();
    }

    public function test_party_suggestions_are_proxied_through_the_internal_platform_connection(): void
    {
        Http::fake([
            'platform.example.test/*' => Http::response([
                'suggestions' => [[
                    'inn' => '7707083893',
                    'account_name' => 'ПАО СБЕРБАНК',
                    'full_name' => 'Полное название не передаётся в браузер',
                ]],
            ]),
        ]);

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.20'])
            ->postJson(route('cabinet.register.party-suggestions'), ['query' => 'Сбер'])
            ->assertOk()
            ->assertExactJson(['suggestions' => [[
                'inn' => '7707083893',
                'account_name' => 'ПАО СБЕРБАНК',
            ]]])
            ->assertHeader('Cache-Control', 'no-store, private');

        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://platform.example.test/api/v1/landing-registration/party-suggestions'
            && $request['query'] === 'Сбер'
            && $request['origin'] === 'https://landing.example.test'
            && $request['client_ip'] === '203.0.113.20'
            && $request->hasHeader('Authorization', 'Bearer '.self::SECRET));
    }

    public function test_missing_party_suggestions_are_returned_as_an_empty_list(): void
    {
        Http::fake([
            'platform.example.test/*' => Http::response(['suggestions' => []], 404),
        ]);

        $this->postJson(route('cabinet.register.party-suggestions'), ['query' => 'Неизвестная компания'])
            ->assertOk()
            ->assertExactJson(['suggestions' => []]);
    }

    public function test_invalid_platform_handoff_is_rejected(): void
    {
        Http::fake([
            'platform.example.test/*' => Http::response([
                'ticket' => self::TICKET,
                'handoff_url' => 'https://attacker.example/steal',
                'method' => 'POST',
                'expires_in' => 60,
            ]),
        ]);

        $this->postJson(route('cabinet.login'), $this->loginPayload())
            ->assertServiceUnavailable()
            ->assertJsonMissing(['ticket' => self::TICKET]);
    }

    public function test_invalid_credentials_return_safe_error(): void
    {
        Http::fake([
            'platform.example.test/*' => Http::response([
                'code' => 'invalid_credentials',
                'message' => 'Internal details',
            ], 401),
        ]);

        $this->postJson(route('cabinet.login'), $this->loginPayload())
            ->assertUnprocessable()
            ->assertExactJson(['message' => 'Неверный email или пароль.']);
    }

    public function test_registration_validation_errors_are_safely_forwarded(): void
    {
        Http::fake([
            'platform.example.test/*' => Http::response([
                'message' => 'Debug details',
                'errors' => [
                    'email' => ['Пользователь с таким email уже существует.'],
                    'internal_trace' => ['must not leak'],
                ],
            ], 422),
        ]);

        $payload = [
            'name' => 'Иван Петров',
            'account_name' => 'ООО Вектор',
            'inn' => '7707083893',
            'email' => 'owner@example.test',
            'phone' => '+79991112233',
            'password' => 'strong-password',
            'password_confirmation' => 'strong-password',
            'capabilities' => ['forwarder'],
            'terms_accepted' => true,
            'privacy_policy_accepted' => true,
        ];

        $this->withHeader('User-Agent', 'Landing browser')
            ->postJson(route('cabinet.register'), $payload)
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Пользователь с таким email уже существует.')
            ->assertJsonPath('errors.email.0', 'Пользователь с таким email уже существует.')
            ->assertJsonMissing(['internal_trace' => ['must not leak']]);
    }

    public function test_login_and_registration_can_be_disabled_independently(): void
    {
        SiteSetting::instance()->update(['cabinet_login_enabled' => false]);
        app(SiteSettingsService::class)->clearCache();
        app(LandingPageService::class)->clearCache();

        $this->get('/')
            ->assertDontSee('data-cabinet-login-open', false)
            ->assertSee('data-cabinet-registration-open', false);

        $this->postJson(route('cabinet.login'), $this->loginPayload())->assertServiceUnavailable();
    }

    public function test_internal_connect_ip_is_required_to_enable_authentication(): void
    {
        SiteSetting::instance()->update(['cabinet_login_connect_ip' => null]);
        app(SiteSettingsService::class)->clearCache();
        app(LandingPageService::class)->clearCache();

        $this->get('/')
            ->assertDontSee('data-cabinet-login-open', false)
            ->assertDontSee('data-cabinet-registration-open', false);
    }

    /** @return array{email: string, password: string, remember: bool} */
    private function loginPayload(): array
    {
        return [
            'email' => 'user@example.test',
            'password' => 'secret-password',
            'remember' => false,
        ];
    }

    /** @return array{ticket: string, handoff_url: string, method: string, expires_in: int} */
    private function handoffPayload(): array
    {
        return [
            'ticket' => self::TICKET,
            'handoff_url' => 'https://platform.example.test/auth/landing/consume',
            'method' => 'POST',
            'expires_in' => 60,
        ];
    }
}
