<?php

namespace App\Http\Controllers;

use App\Http\Requests\WeatherRequest;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use UnexpectedValueException;

class WeatherController extends Controller
{
    public function show(WeatherRequest $request): JsonResponse
    {
        $data = $request->validated();
        $key = config('services.openweather.key');
        if (! $key) {
            return response()->json(['message' => 'Weather is not configured yet.'], 503);
        }
        $lat = number_format((float) $data['lat'], 4, '.', '');
        $lon = number_format((float) $data['lon'], 4, '.', '');

        try {
            $weather = Cache::remember('weather:current:'.$lat.':'.$lon, now()->addMinutes(10), function () use ($lat, $lon, $key): array {
                $response = Http::acceptJson()->connectTimeout(3)->timeout(10)
                    ->get('https://api.openweathermap.org/data/2.5/weather', [
                        'lat' => $lat, 'lon' => $lon, 'units' => 'metric', 'appid' => $key,
                    ]);
                $weather = $response->json();
                if (! $response->successful() || ! is_numeric(data_get($weather, 'main.temp'))) {
                    throw new UnexpectedValueException('Weather provider unavailable.');
                }

                return [
                    'place' => $weather['name'] ?? 'Selected location',
                    'temperature' => (float) $weather['main']['temp'],
                    'description' => data_get($weather, 'weather.0.description'),
                    'humidity' => data_get($weather, 'main.humidity'),
                    'wind_speed' => data_get($weather, 'wind.speed'),
                    'observed_at' => $weather['dt'] ?? null,
                ];
            });
        } catch (ConnectionException|UnexpectedValueException) {
            return response()->json(['message' => 'Weather is currently unavailable. Please try again later.'], 503);
        }

        return response()->json($weather);
    }
}
