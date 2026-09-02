<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use App\Services\Community\TelegramIdTokenVerifier;
use App\Services\SiteSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TelegramIdTokenVerifierTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_verifies_signature_claims_audience_and_nonce(): void
    {
        [$privateKey, $jwk] = $this->keyPair();
        SiteSetting::instance()->update(['community_telegram_client_id' => '12345']);
        app(SiteSettingsService::class)->clearCache();
        Http::fake(['https://oauth.telegram.org/.well-known/jwks.json' => Http::response(['keys' => [$jwk]])]);
        $token = $this->token($privateKey, [
            'iss' => 'https://oauth.telegram.org', 'aud' => '12345', 'sub' => 'telegram-user',
            'iat' => time() - 5, 'exp' => time() + 300, 'nonce' => 'nonce-value',
        ]);

        $claims = app(TelegramIdTokenVerifier::class)->verify($token, 'nonce-value');
        $this->assertSame('telegram-user', $claims['sub']);
    }

    public function test_it_rejects_wrong_audience(): void
    {
        [$privateKey, $jwk] = $this->keyPair();
        SiteSetting::instance()->update(['community_telegram_client_id' => 'expected']);
        app(SiteSettingsService::class)->clearCache();
        Http::fake(['https://oauth.telegram.org/.well-known/jwks.json' => Http::response(['keys' => [$jwk]])]);
        $token = $this->token($privateKey, [
            'iss' => 'https://oauth.telegram.org', 'aud' => 'other', 'sub' => 'user',
            'iat' => time(), 'exp' => time() + 300, 'nonce' => 'nonce',
        ]);

        $this->expectException(ValidationException::class);
        app(TelegramIdTokenVerifier::class)->verify($token, 'nonce');
    }

    /** @return array{0: \OpenSSLAsymmetricKey, 1: array<string, string>} */
    private function keyPair(): array
    {
        $private = openssl_pkey_get_private(<<<'PEM'
-----BEGIN PRIVATE KEY-----
MIIEvgIBADANBgkqhkiG9w0BAQEFAASCBKgwggSkAgEAAoIBAQC99ZvjOeqrEl6A
sWYnjTpRTbjAAtrOvfpYzr5BfCI+AEiVwFtFIDsd9k4DFA1sfkg3ffv1G1HbWpA/
zbhAjm7w0dVW3JbeKan33eZmFkUwqcYVt/Ggq+bvtFw5Xnz96KLTeeSi+Xjya00I
GaX6VJjqs6SqP1q8iAsetE1BwxmbH6IN1yyZ9Jscmcq4thtk3nOnBGkQDfPngwVI
1GQEsGqahLRDV7tK5lR/eM/deY744l1Df/DXcWk7aCrdhqWB0Y1AWf6tZcShzSD3
Blzoe+irwMNoZZpr9RYo/ffDyo1NQ7/KYirdNOA5ts5O1Ky3yw7cef5TTR4lkzuL
X0jjaEBfAgMBAAECggEAAp5ptUK9pPkeW2jWrzHnvWo0Y1VGvfXphyLmhZGYCUHI
vmPF/lh46CtWnP1ASCAfj8BJYnIreTow5ehIo38zMVe7OLTW0Z5CbRveisbA0nBs
vizohF4KU4CpitLuJzP3VrIGAGcvDyyChduQqbhT2Vx7pyszbYj0mkpDDGaLqlNJ
uRpIosmsNi+FFpKrLKv7LxlucTEXt8nSL/US2OEuFsO1t20kNsMCml7WHv+sAIU8
X1CjDP/D91DssHl+E/Q6vakmY20XxBCbEU0qojylkqaWHi8O2ISD6sV1jaBEGBiu
fFbeuyvZ0cpgcVggS8ayyUHv+/qYOJzEvbFY9BVpkQKBgQDrwrGD+U37Z17+0j7B
Jlusvp9TKsskOhz8mRM3xU1V5FRDk8QbSti0SSxc/JgsNTwfDN486u3QFbdzyght
g6QgDSLXvNsItdL0AqaL9tP85f1TI9ZzfNvdvjNzM1iMwpnrRVImzFH/5IixVFyh
66HAkOPsjWxsrza/S2M/PBpOEQKBgQDORFhPNnOfUrPZDKUKz9JLoYbKohlTv6nC
evqa5/fYO9eeH63LYQjY6hi9ukNHys6mU9A1g7q33eTZP1wPobh+Fw3FKjUcNqyk
1bT6n2g5Cm3LAKYYLcGvn4chy5LOtlyYlj7jnI0Gr4LEOsSlh7xXzLogh82nD8tQ
V8Kekc73bwKBgQCmlGAdIa/J7NYgMqmIi/PGcMHeX6Q9KpqEmBwOEeh8weIQBX5Q
0mefqvwfj5Jt2gdq4Qq9/DigCFghBiVCS/tRcjamPJh+5Qnyw1SuHG7YiCCf9/h0
jUpEuTldMnBktLNQmyBarY9awT0cHsF83yLhDv8ciyiK+poyO7AaY/sqIQKBgFXK
Q1ie8zQe98Kc6cA3c+YVBrUc+p/EpdzHvZfUR1defG2+C1D9yOo/Y471+6nOhmjT
j1PSuERlPBvHqIiv4MZA3G9XAMP9UY01fZKH3pdq6QN3/50q2tYq6c96llL3skec
pgeRQ262bRMkxkl6zTFPa1LAaR7FCPEfr0i4qHZtAoGBAN/EoVmpCDxNH6Wzw4GZ
7NPg9fuh8nuaZye/WGhskyjzkSQ8RBi4SeZXJ+fdRFt0aVwUfIGwZZSJOWaZCE3v
6iplcCHG4cOjZdrvTwYCLCfOggkvHR8++cNyYJcZGdvzyHXKQXR4C38Yw+SsQUtW
1+V7YgYF+crQd7C54yEpWbqy
-----END PRIVATE KEY-----
PEM);
        $this->assertInstanceOf(\OpenSSLAsymmetricKey::class, $private);
        $details = openssl_pkey_get_details($private);

        return [$private, ['kty' => 'RSA', 'kid' => 'test-key', 'alg' => 'RS256', 'n' => $this->b64($details['rsa']['n']), 'e' => $this->b64($details['rsa']['e'])]];
    }

    /** @param array<string, mixed> $payload */
    private function token(\OpenSSLAsymmetricKey $private, array $payload): string
    {
        $header = $this->b64(json_encode(['alg' => 'RS256', 'typ' => 'JWT', 'kid' => 'test-key']));
        $body = $this->b64(json_encode($payload));
        openssl_sign($header.'.'.$body, $signature, $private, OPENSSL_ALGO_SHA256);

        return $header.'.'.$body.'.'.$this->b64($signature);
    }

    private function b64(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
