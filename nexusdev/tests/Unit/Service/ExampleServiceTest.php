<?php

namespace App\Tests\Unit\Service;

use App\Service\WeatherService;
use PHPUnit\Framework\TestCase;

/**
 * Exemple de test simple pour un service
 */
class ExampleServiceTest extends TestCase
{
    private WeatherService $weatherService;

    protected function setUp(): void
    {
        // Initialiser le service à tester
        $this->weatherService = new WeatherService();
    }

    public function testGetCurrentWeatherReturnsData(): void
    {
        // Données de test
        $city = 'Paris';
        
        // Exécuter la méthode à tester
        $result = $this->weatherService->getCurrentWeather($city);
        
        // Vérifications (assertions)
        $this->assertIsArray($result);
        $this->assertArrayHasKey('temperature', $result);
        $this->assertArrayHasKey('description', $result);
        $this->assertEquals(15, $result['temperature']);
        $this->assertEquals('Partly cloudy', $result['description']);
    }

    public function testGetCurrentWeatherWithUnknownCity(): void
    {
        // Tester le cas d'erreur
        $result = $this->weatherService->getCurrentWeather('UnknownCity');
        
        $this->assertNull($result);
    }

    public function testGetCurrentWeatherWithEmptyString(): void
    {
        // Tester les cas limites
        $result = $this->weatherService->getCurrentWeather('');
        
        $this->assertNull($result);
    }
}
