<?php

namespace App\Tests\Integration\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Response;

class ApiControllerIntegrationTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    public function testWeatherApiWithValidCity(): void
    {
        // Appeler l'API météo
        $this->client->request('GET', '/weather/api/Paris');
        
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/json');
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('temperature', $responseData);
        $this->assertArrayHasKey('description', $responseData);
        $this->assertArrayHasKey('humidity', $responseData);
        $this->assertArrayHasKey('wind_speed', $responseData);
    }

    public function testWeatherApiWithUnknownCity(): void
    {
        $this->client->request('GET', '/weather/api/UnknownCity');
        
        $this->assertResponseStatusCodeSame(404);
        $this->assertResponseHeaderSame('content-type', 'application/json');
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
    }

    public function testWeatherApiWithEmptyCity(): void
    {
        $this->client->request('GET', '/weather/api/');
        
        $this->assertResponseStatusCodeSame(404);
    }

    public function testWeatherWidgetPage(): void
    {
        $this->client->request('GET', '/weather/widget');
        
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('.np-card');
    }

    public function testRiotApiSyncStatsWithoutAuth(): void
    {
        // Tester l'API Riot sans authentification
        $this->client->request('POST', '/player/riot/sync-stats', [
            'json' => ['playerId' => 123]
        ]);
        
        // Devrait rediriger vers login
        $this->assertResponseRedirects('/login');
    }

    public function testApiReturnsJsonContentType(): void
    {
        $this->client->request('GET', '/weather/api/Paris');
        
        $response = $this->client->getResponse();
        $this->assertStringContainsString('application/json', $response->headers->get('content-type'));
    }

    public function testApiHandlesInvalidJson(): void
    {
        // Envoyer du JSON invalide
        $this->client->request('POST', '/player/riot/sync-stats', [
            'json' => 'invalid json'
        ]);
        
        // Devrait rediriger vers login car non authentifié
        $this->assertResponseRedirects('/login');
    }

    private function createTestUser(): \App\Entity\User
    {
        $user = new \App\Entity\User();
        $user->setEmail('test@example.com');
        $user->setPassword('password123');
        
        $entityManager = static::getContainer()->get('doctrine.orm.entity_manager');
        $entityManager->persist($user);
        $entityManager->flush();
        
        return $user;
    }
}
