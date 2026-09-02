<?php

namespace App\Services\Community;

use App\Services\SiteSettingsService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

final class TelegramIdTokenVerifier
{
    public function __construct(private readonly SiteSettingsService $settings) {}

    /** @return array<string, mixed> */
    public function verify(string $jwt, string $nonce): array
    {
        $parts = explode('.', $jwt);

        if (count($parts) !== 3) {
            throw $this->invalid();
        }

        [$encodedHeader, $encodedPayload, $encodedSignature] = $parts;
        $header = json_decode($this->base64UrlDecode($encodedHeader), true);
        $payload = json_decode($this->base64UrlDecode($encodedPayload), true);

        if (! is_array($header) || ! is_array($payload) || ($header['alg'] ?? null) !== 'RS256' || ! isset($header['kid'])) {
            throw $this->invalid();
        }

        $jwk = collect($this->jwks()['keys'] ?? [])->firstWhere('kid', $header['kid']);

        if (! is_array($jwk) || ! isset($jwk['n'], $jwk['e'])) {
            throw $this->invalid();
        }

        $verified = openssl_verify(
            $encodedHeader.'.'.$encodedPayload,
            $this->base64UrlDecode($encodedSignature),
            $this->rsaPublicKey($jwk['n'], $jwk['e']),
            OPENSSL_ALGO_SHA256,
        );

        $clientId = $this->settings->telegramClientId();
        $audience = $payload['aud'] ?? null;
        $audienceMatches = is_array($audience) ? in_array($clientId, $audience, true) : (string) $audience === $clientId;

        if ($verified !== 1
            || ($payload['iss'] ?? null) !== config('community.telegram.issuer')
            || ! $audienceMatches
            || ! isset($payload['sub'], $payload['exp'], $payload['iat'])
            || (int) $payload['exp'] < time()
            || (int) $payload['iat'] > time() + 30
            || ! hash_equals($nonce, (string) ($payload['nonce'] ?? ''))) {
            throw $this->invalid();
        }

        return $payload;
    }

    /** @return array<string, mixed> */
    private function jwks(): array
    {
        return Cache::remember('community.telegram.jwks', now()->addHours(6), function (): array {
            return Http::timeout(5)
                ->get('https://oauth.telegram.org/.well-known/jwks.json')
                ->throw()
                ->json();
        });
    }

    private function rsaPublicKey(string $modulus, string $exponent): string
    {
        $modulus = $this->asn1Integer($this->base64UrlDecode($modulus));
        $exponent = $this->asn1Integer($this->base64UrlDecode($exponent));
        $sequence = $this->asn1Sequence($modulus.$exponent);
        $algorithm = hex2bin('300d06092a864886f70d0101010500');
        $bitString = "\x03".$this->asn1Length(strlen($sequence) + 1)."\x00".$sequence;
        $der = $this->asn1Sequence($algorithm.$bitString);

        return "-----BEGIN PUBLIC KEY-----\n".chunk_split(base64_encode($der), 64, "\n")."-----END PUBLIC KEY-----\n";
    }

    private function asn1Integer(string $value): string
    {
        if (ord($value[0]) > 0x7F) {
            $value = "\x00".$value;
        }

        return "\x02".$this->asn1Length(strlen($value)).$value;
    }

    private function asn1Sequence(string $value): string
    {
        return "\x30".$this->asn1Length(strlen($value)).$value;
    }

    private function asn1Length(int $length): string
    {
        if ($length < 128) {
            return chr($length);
        }

        $encoded = ltrim(pack('N', $length), "\x00");

        return chr(0x80 | strlen($encoded)).$encoded;
    }

    private function base64UrlDecode(string $value): string
    {
        $decoded = base64_decode(strtr($value, '-_', '+/').str_repeat('=', (4 - strlen($value) % 4) % 4), true);

        if ($decoded === false) {
            throw $this->invalid();
        }

        return $decoded;
    }

    private function invalid(): ValidationException
    {
        return ValidationException::withMessages(['telegram' => 'Не удалось подтвердить вход через Telegram.']);
    }
}
