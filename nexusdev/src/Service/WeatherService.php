<?php

namespace App\Service;

class WeatherService
{
    public function getCurrentWeather(string $city): ?array
    {
        // Mock weather data - in a real app, this would call a weather API
        $weatherData = [
            'Paris' => [
                'temperature' => 15,
                'description' => 'Partly cloudy',
                'humidity' => 65,
                'wind_speed' => 10,
                'icon' => 'partly-cloudy'
            ],
            'London' => [
                'temperature' => 12,
                'description' => 'Rainy',
                'humidity' => 80,
                'wind_speed' => 15,
                'icon' => 'rainy'
            ],
            'New York' => [
                'temperature' => 18,
                'description' => 'Sunny',
                'humidity' => 55,
                'wind_speed' => 8,
                'icon' => 'sunny'
            ]
        ];

        return $weatherData[$city] ?? null;
    }
}
