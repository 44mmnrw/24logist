<?php

namespace App\Services\Community;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class MaxApiClient
{
    private const CA_BUNDLE = 'certificates/russian-trusted-root-ca.pem';

    public function request(): PendingRequest
    {
        $caBundle = resource_path(self::CA_BUNDLE);

        if (! is_readable($caBundle)) {
            throw new RuntimeException('Не найден сертификат Минцифры для защищённого соединения с API MAX.');
        }

        return Http::withOptions([
            'verify' => $caBundle,
        ]);
    }
}
