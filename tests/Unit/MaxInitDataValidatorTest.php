<?php

namespace Tests\Unit;

use App\Models\SiteSetting;
use App\Services\Community\MaxInitDataValidator;
use App\Services\SiteSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class MaxInitDataValidatorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        SiteSetting::instance()->update(['community_max_bot_token' => 'validator-token']);
        app(SiteSettingsService::class)->clearCache();
    }

    public function test_it_rejects_tampering_expiry_and_duplicate_parameters(): void
    {
        $valid = $this->signed(time());
        $this->assertSame(42, app(MaxInitDataValidator::class)->validate($valid)['user']['id']);

        foreach ([$valid.'x', $this->signed(time() - 4000), $valid.'&auth_date='.time(), $this->signed(time(), false)] as $invalid) {
            try {
                app(MaxInitDataValidator::class)->validate($invalid);
                $this->fail('Invalid MAX payload must be rejected.');
            } catch (ValidationException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    private function signed(int $authDate, bool $includeQueryId = true): string
    {
        $data = [
            'auth_date' => (string) $authDate,
            'user' => json_encode(['id' => 42]),
        ];
        if ($includeQueryId) {
            $data['query_id'] = 'validator-query';
        }
        ksort($data);
        $check = implode("\n", array_map(fn ($key, $value) => $key.'='.$value, array_keys($data), array_values($data)));
        $secret = hash_hmac('sha256', 'validator-token', 'WebAppData', true);
        $data['hash'] = hash_hmac('sha256', $check, $secret);

        return http_build_query($data, '', '&', PHP_QUERY_RFC3986);
    }
}
