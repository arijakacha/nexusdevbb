<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Player;
use App\Entity\Team;
use App\Entity\Game;
use PHPUnit\Framework\TestCase;

/**
 * Exemple de test pour une entité
 */
class ExampleEntityTest extends TestCase
{
    public function testPlayerCreation(): void
    {
        // Créer une nouvelle entité
        $player = new Player();
        
        // Créer un jeu mock pour le constructeur
        $game = $this->createMock(Game::class);
        
        // Tester les setters (si existants)
        $player->setNickname('TestPlayer');
        $player->setRealName('John Doe');
        $player->setScore(1500);
        $player->setIsPro(true);
        
        // Vérifications
        $this->assertEquals('TestPlayer', $player->getNickname());
        $this->assertEquals('John Doe', $player->getRealName());
        $this->assertEquals(1500, $player->getScore());
        $this->assertTrue($player->isPro());
    }

    public function testPlayerTeamRelationship(): void
    {
        $player = new Player();
        $team = new Team();
        
        // Tester la relation
        $player->setTeam($team);
        
        $this->assertEquals($team, $player->getTeam());
    }

    public function testPlayerDefaultValues(): void
    {
        $player = new Player();
        
        // Tester les valeurs par défaut
        $this->assertNull($player->getId());
        $this->assertNull($player->getTeam());
        $this->assertFalse($player->isPro());
        $this->assertEquals(0, $player->getScore());
    }
}
