<?php

declare(strict_types=1);

const MAX_BODY_BYTES = 32768;
const MAX_EVENTS = 10;

header('Cache-Control: no-store, max-age=0');
header('Content-Type: application/json; charset=utf-8');
header('Referrer-Policy: no-referrer');
header('X-Content-Type-Options: nosniff');
header_remove('X-Powered-By');

$config = loadConfig();
$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
$origin = rtrim((string) ($_SERVER['HTTP_ORIGIN'] ?? ''), '/');
$allowedOrigins = array_values(array_filter(array_map(
    static fn (string $value): string => rtrim(trim($value), '/'),
    explode(',', (string) ($config['ANALYTICS_ALLOWED_ORIGINS'] ?? 'https://24logist.ru,https://www.24logist.ru')),
)));

if ($origin !== '' && in_array($origin, $allowedOrigins, true)) {
    header('Access-Control-Allow-Origin: '.$origin);
    header('Vary: Origin');
}

if ($method === 'OPTIONS') {
    if ($origin === '' || ! in_array($origin, $allowedOrigins, true)) {
        respond(403, ['status' => 'origin_denied']);
    }

    header('Access-Control-Allow-Headers: Content-Type');
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Max-Age: 600');
    http_response_code(204);
    exit;
}

if ($method === 'GET') {
    respond(200, [
        'status' => 'ok',
        'configured' => collectorIsConfigured($config),
    ]);
}

if ($method !== 'POST') {
    header('Allow: GET, POST, OPTIONS');
    respond(405, ['status' => 'method_not_allowed']);
}

if ($origin === '' || ! in_array($origin, $allowedOrigins, true)) {
    respond(403, ['status' => 'origin_denied']);
}

if (! collectorIsConfigured($config)) {
    respond(503, ['status' => 'not_configured']);
}

$contentType = strtolower(trim(explode(';', (string) ($_SERVER['CONTENT_TYPE'] ?? ''), 2)[0]));
if (! in_array($contentType, ['application/json', 'text/plain'], true)) {
    respond(415, ['status' => 'unsupported_media_type']);
}

$declaredLength = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);
if ($declaredLength > MAX_BODY_BYTES) {
    respond(413, ['status' => 'payload_too_large']);
}

$rawBody = file_get_contents('php://input');
if (! is_string($rawBody) || $rawBody === '' || strlen($rawBody) > MAX_BODY_BYTES) {
    respond(400, ['status' => 'invalid_payload']);
}

try {
    $input = json_decode($rawBody, true, 16, JSON_THROW_ON_ERROR);
} catch (JsonException) {
    respond(400, ['status' => 'invalid_json']);
}

if (! is_array($input) || ($input['consent'] ?? null) !== true) {
    respond(400, ['status' => 'consent_required']);
}

$clientId = (string) ($input['client_id'] ?? '');
if (! preg_match('/^[1-9]\d{0,19}\.[1-9]\d{0,19}$/', $clientId)) {
    respond(422, ['status' => 'invalid_client_id']);
}

$sessionId = $input['session_id'] ?? null;
if ((! is_int($sessionId) && ! ctype_digit((string) $sessionId)) || (int) $sessionId <= 0) {
    respond(422, ['status' => 'invalid_session_id']);
}

$inputEvents = $input['events'] ?? null;
if (! is_array($inputEvents) || $inputEvents === [] || count($inputEvents) > MAX_EVENTS) {
    respond(422, ['status' => 'invalid_events']);
}

$events = [];
foreach ($inputEvents as $inputEvent) {
    $event = sanitizeEvent($inputEvent, (int) $sessionId);
    if ($event === null) {
        respond(422, ['status' => 'invalid_event']);
    }

    $events[] = $event;
}

$payload = [
    'client_id' => $clientId,
    'consent' => [
        'ad_user_data' => 'DENIED',
        'ad_personalization' => 'DENIED',
    ],
    'events' => $events,
];

$query = http_build_query([
    'measurement_id' => $config['GA4_MEASUREMENT_ID'],
    'api_secret' => $config['GA4_API_SECRET'],
], '', '&', PHP_QUERY_RFC3986);

$curl = curl_init('https://www.google-analytics.com/mp/collect?'.$query);
if ($curl === false) {
    respond(502, ['status' => 'upstream_unavailable']);
}

curl_setopt_array($curl, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CONNECTTIMEOUT_MS => 1500,
    CURLOPT_TIMEOUT_MS => 3500,
]);

$result = curl_exec($curl);
$upstreamStatus = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
$upstreamError = curl_errno($curl);
curl_close($curl);

if ($result === false || $upstreamError !== 0 || $upstreamStatus < 200 || $upstreamStatus >= 300) {
    error_log('Analytics upstream request failed with HTTP status '.$upstreamStatus.'.');
    respond(502, ['status' => 'upstream_unavailable']);
}

http_response_code(204);
exit;

/** @return array<string, string> */
function loadConfig(): array
{
    $configuredPath = getenv('ANALYTICS_ENV_FILE');
    $envPath = is_string($configuredPath) && $configuredPath !== ''
        ? $configuredPath
        : dirname(__DIR__, 2).'/.analytics.env';

    if (! is_file($envPath) || ! is_readable($envPath)) {
        return [];
    }

    $values = parse_ini_file($envPath, false, INI_SCANNER_RAW);

    if (! is_array($values)) {
        return [];
    }

    return array_map(
        static fn (mixed $value): string => trim((string) $value, " \n\r\t\v\0\"'"),
        $values,
    );
}

/** @param array<string, string> $config */
function collectorIsConfigured(array $config): bool
{
    $measurementId = $config['GA4_MEASUREMENT_ID'] ?? '';
    $apiSecret = $config['GA4_API_SECRET'] ?? '';

    return ($config['ANALYTICS_ENABLED'] ?? 'false') === 'true'
        && $measurementId !== 'G-XXXXXXXXXX'
        && preg_match('/^G-[A-Z0-9]{4,20}$/', $measurementId) === 1
        && $apiSecret !== ''
        && $apiSecret !== 'replace-on-server';
}

/** @return array{name: string, params: array<string, bool|float|int|string>}|null */
function sanitizeEvent(mixed $inputEvent, int $sessionId): ?array
{
    if (! is_array($inputEvent)) {
        return null;
    }

    $allowedNames = [
        'click',
        'form_start',
        'form_submit',
        'generate_lead',
        'page_view',
        'scroll',
    ];
    $name = (string) ($inputEvent['name'] ?? '');
    if (! in_array($name, $allowedNames, true)) {
        return null;
    }

    $allowedParams = [
        'currency',
        'engagement_time_msec',
        'form_id',
        'form_name',
        'link_text',
        'link_url',
        'method',
        'page_location',
        'page_referrer',
        'page_title',
        'value',
    ];
    $inputParams = $inputEvent['params'] ?? [];
    if (! is_array($inputParams)) {
        return null;
    }

    $params = [
        'session_id' => $sessionId,
        'engagement_time_msec' => 1,
    ];

    foreach ($allowedParams as $key) {
        if (! array_key_exists($key, $inputParams)) {
            continue;
        }

        $value = $inputParams[$key];
        if (! is_string($value) && ! is_int($value) && ! is_float($value) && ! is_bool($value)) {
            return null;
        }

        if (is_string($value)) {
            $value = trim($value);
            if (in_array($key, ['page_location', 'page_referrer', 'link_url'], true)) {
                $value = sanitizeUrl($value);
            } else {
                $value = truncateUtf8($value, 500);
            }
        }

        if ($key === 'engagement_time_msec') {
            $value = max(1, min(3600000, (int) $value));
        }

        $params[$key] = $value;
    }

    return ['name' => $name, 'params' => $params];
}

function sanitizeUrl(string $value): string
{
    $parts = parse_url($value);
    if (! is_array($parts) || ! isset($parts['scheme'], $parts['host'])) {
        return '';
    }

    if (! in_array(strtolower((string) $parts['scheme']), ['http', 'https'], true)) {
        return '';
    }

    $port = isset($parts['port']) ? ':'.(int) $parts['port'] : '';

    return truncateUtf8(
        strtolower((string) $parts['scheme']).'://'.strtolower((string) $parts['host']).$port.($parts['path'] ?? '/'),
        500,
    );
}

function truncateUtf8(string $value, int $length): string
{
    if (function_exists('mb_substr')) {
        return mb_substr($value, 0, $length, 'UTF-8');
    }

    return preg_replace('/^(.{0,'.$length.'}).*$/us', '$1', $value) ?? '';
}

/** @param array<string, bool|string> $body */
function respond(int $status, array $body): never
{
    http_response_code($status);
    echo json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}
