<?php

namespace App\Http\Controllers;

use App\Services\RouteCalculator\PlatformRouteApiClient;
use App\Services\SiteSettingsService;
use Illuminate\Http\Client\Response as ClientResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

final class RouteCalculatorController extends Controller
{
    public function __construct(
        private readonly SiteSettingsService $settings,
        private readonly PlatformRouteApiClient $client,
    ) {}

    public function index(): View
    {
        abort_unless($this->settings->routeApiConfigured(), 404);

        return view('route-calculator');
    }

    public function cities(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'query' => ['required', 'string', 'min:2', 'max:255'],
        ]);

        return $this->proxy(fn (): ClientResponse => $this->client->get('city-suggest', $validated));
    }

    public function calculate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from_city' => ['required', 'string', 'max:120'],
            'to_city' => ['required', 'string', 'max:120'],
            'price_per_km' => ['nullable', 'numeric', 'min:0', 'max:999999'],
            'driver_work_hours_per_day' => ['nullable', 'numeric', 'min:0.5', 'max:24'],
            'max_km_per_day' => ['nullable', 'numeric', 'min:1', 'max:2000'],
            'toll_mode' => ['nullable', 'in:avoid_toll,prefer_toll'],
            'routing_profile' => ['nullable', 'in:car,truck'],
            'truck' => ['nullable', 'array'],
            'truck.gross_weight_t' => ['nullable', 'numeric', 'gt:0', 'max:500'],
            'truck.max_axle_load_t' => ['nullable', 'numeric', 'gt:0', 'max:100'],
            'truck.height_m' => ['nullable', 'numeric', 'gt:0', 'max:20'],
            'truck.width_m' => ['nullable', 'numeric', 'gt:0', 'max:20'],
            'truck.length_m' => ['nullable', 'numeric', 'gt:0', 'max:100'],
            'truck.axle_count' => ['nullable', 'integer', 'min:1', 'max:32'],
            'truck.hazmat' => ['nullable', 'boolean'],
        ]);

        return $this->proxy(fn (): ClientResponse => $this->client->post('calculate', $validated));
    }

    /** @param callable(): ClientResponse $request */
    private function proxy(callable $request): JsonResponse
    {
        if (! $this->settings->routeApiConfigured()) {
            return response()->json(['message' => 'Калькулятор маршрута пока не настроен.'], 503);
        }

        try {
            $response = $request();
            $payload = $response->json();

            if (! is_array($payload)) {
                return response()->json(['message' => 'Платформа вернула некорректный ответ.'], 502);
            }

            $status = $response->status();
            if ($status === 401 || $status === 403) {
                return response()->json([
                    'message' => 'Проверьте API-секрет в настройках сайта и платформы.',
                ], 503);
            }

            if ($status >= 500 || $status < 200) {
                return response()->json([
                    'message' => (string) ($payload['message'] ?? 'Платформа расчёта маршрута временно недоступна.'),
                ], 502);
            }

            return response()->json($payload, $status);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 503);
        }
    }
}
