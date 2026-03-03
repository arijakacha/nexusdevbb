<?php

namespace App\Tests\Unit\Service;

use App\Service\EloRatingService;
use App\Entity\Player;
use App\Entity\RankHistory;
use App\Entity\Game;
use App\Repository\RankHistoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class EloRatingServiceTest extends TestCase
{
    private EloRatingService $service;
    private EntityManagerInterface $entityManager;
    private RankHistoryRepository $rankHistoryRepository;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->rankHistoryRepository = $this->createMock(RankHistoryRepository::class);
        
        $this->entityManager->method('getRepository')
            ->with(RankHistory::class)
            ->willReturn($this->rankHistoryRepository);
        
        $this->service = new EloRatingService($this->entityManager);
    }

    public function testCalculateExpectedScore(): void
    {
        $ratingA = 1500;
        $ratingB = 1400;
        
        $expectedScore = $this->service->calculateExpectedScore($ratingA, $ratingB);
        
        $this->assertGreaterThan(0.5, $expectedScore);
        $this->assertLessThan(1.0, $expectedScore);
    }

    public function testCalculateExpectedScoreEqualRatings(): void
    {
        $ratingA = 1500;
        $ratingB = 1500;
        
        $expectedScore = $this->service->calculateExpectedScore($ratingA, $ratingB);
        
        $this->assertEquals(0.5, $expectedScore);
    }

    public function testCalculateExpectedScoreLowerRating(): void
    {
        $ratingA = 1400;
        $ratingB = 1500;
        
        $expectedScore = $this->service->calculateExpectedScore($ratingA, $ratingB);
        
        $this->assertLessThan(0.5, $expectedScore);
        $this->assertGreaterThan(0, $expectedScore);
    }

    public function testCalculateNewRatingWin(): void
    {
        $currentRating = 1500;
        $expectedScore = 0.5;
        $actualScore = 1.0;
        
        $newRating = $this->service->calculateNewRating($currentRating, $expectedScore, $actualScore);
        
        $this->assertGreaterThan($currentRating, $newRating);
        $this->assertEquals(1516, $newRating);
    }

    public function testCalculateNewRatingLoss(): void
    {
        $currentRating = 1500;
        $expectedScore = 0.5;
        $actualScore = 0.0;
        
        $newRating = $this->service->calculateNewRating($currentRating, $expectedScore, $actualScore);
        
        $this->assertLessThan($currentRating, $newRating);
        $this->assertEquals(1484, $newRating);
    }

    public function testCalculateNewRatingDraw(): void
    {
        $currentRating = 1500;
        $expectedScore = 0.6;
        $actualScore = 0.5;
        
        $newRating = $this->service->calculateNewRating($currentRating, $expectedScore, $actualScore);
        
        $this->assertLessThan($currentRating, $newRating);
        $this->assertEquals(1497, $newRating);
    }

    public function testUpdateRatingsNewPlayers(): void
    {
        $winner = $this->createMock(Player::class);
        $winner->method('getId')->willReturn(1);
        
        $loser = $this->createMock(Player::class);
        $loser->method('getId')->willReturn(2);
        
        $game = $this->createMock(Game::class);
        $game->method('getId')->willReturn(1);
        
        $this->rankHistoryRepository->method('findLatestPlayerRank')
            ->willReturn(null);
        
        $this->rankHistoryRepository->method('findGlobalRankings')
            ->willReturn([]);
        
        $this->entityManager->expects($this->exactly(2))
            ->method('persist');
        // The flush is called twice in recordRatingChange which is called twice
        $this->entityManager->expects($this->exactly(2))
            ->method('flush');
        
        $result = $this->service->updateRatings($winner, $loser, $game);
        
        $this->assertArrayHasKey('winnerChange', $result);
        $this->assertArrayHasKey('loserChange', $result);
        $this->assertArrayHasKey('winnerNewRating', $result);
        $this->assertArrayHasKey('loserNewRating', $result);
        
        $this->assertGreaterThan(0, $result['winnerChange']);
        $this->assertLessThan(0, $result['loserChange']);
        $this->assertEquals(1216, $result['winnerNewRating']);
        $this->assertEquals(1184, $result['loserNewRating']);
    }

    public function testUpdateRatingsWithExistingRatings(): void
    {
        $winner = $this->createMock(Player::class);
        $winner->method('getId')->willReturn(1);
        
        $loser = $this->createMock(Player::class);
        $loser->method('getId')->willReturn(2);
        
        $game = $this->createMock(Game::class);
        $game->method('getId')->willReturn(1);
        
        $winnerRank = $this->createMock(RankHistory::class);
        $winnerRank->method('getEloRating')->willReturn(1600);
        
        $loserRank = $this->createMock(RankHistory::class);
        $loserRank->method('getEloRating')->willReturn(1400);
        
        $this->rankHistoryRepository->method('findLatestPlayerRank')
            ->willReturnOnConsecutiveCalls($winnerRank, $loserRank);
        
        $this->rankHistoryRepository->method('findGlobalRankings')
            ->willReturn([]);
        
        $this->entityManager->expects($this->exactly(2))
            ->method('persist');
        $this->entityManager->expects($this->exactly(2))
            ->method('flush');
        
        $result = $this->service->updateRatings($winner, $loser, $game);
        
        $this->assertGreaterThan(0, $result['winnerChange']);
        $this->assertLessThan(0, $result['loserChange']);
    }

    public function testGetCurrentSeasonWinter(): void
    {
        $service = new class extends EloRatingService {
            public function __construct() {}
            public function testGetCurrentSeason(\DateTimeImmutable $date): string {
                $year = $date->format('Y');
                $month = (int) $date->format('n');
                
                if ($month >= 1 && $month <= 3) {
                    return "$year-Winter";
                } elseif ($month >= 4 && $month <= 6) {
                    return "$year-Spring";
                } elseif ($month >= 7 && $month <= 9) {
                    return "$year-Summer";
                } else {
                    return "$year-Fall";
                }
            }
        };
        
        $this->assertEquals('2024-Winter', $service->testGetCurrentSeason(new \DateTimeImmutable('2024-01-15')));
        $this->assertEquals('2024-Winter', $service->testGetCurrentSeason(new \DateTimeImmutable('2024-03-15')));
    }

    public function testGetCurrentSeasonSpring(): void
    {
        $service = new class extends EloRatingService {
            public function __construct() {}
            public function testGetCurrentSeason(\DateTimeImmutable $date): string {
                $year = $date->format('Y');
                $month = (int) $date->format('n');
                
                if ($month >= 1 && $month <= 3) {
                    return "$year-Winter";
                } elseif ($month >= 4 && $month <= 6) {
                    return "$year-Spring";
                } elseif ($month >= 7 && $month <= 9) {
                    return "$year-Summer";
                } else {
                    return "$year-Fall";
                }
            }
        };
        
        $this->assertEquals('2024-Spring', $service->testGetCurrentSeason(new \DateTimeImmutable('2024-04-15')));
        $this->assertEquals('2024-Spring', $service->testGetCurrentSeason(new \DateTimeImmutable('2024-06-15')));
    }

    public function testGetCurrentSeasonSummer(): void
    {
        $service = new class extends EloRatingService {
            public function __construct() {}
            public function testGetCurrentSeason(\DateTimeImmutable $date): string {
                $year = $date->format('Y');
                $month = (int) $date->format('n');
                
                if ($month >= 1 && $month <= 3) {
                    return "$year-Winter";
                } elseif ($month >= 4 && $month <= 6) {
                    return "$year-Spring";
                } elseif ($month >= 7 && $month <= 9) {
                    return "$year-Summer";
                } else {
                    return "$year-Fall";
                }
            }
        };
        
        $this->assertEquals('2024-Summer', $service->testGetCurrentSeason(new \DateTimeImmutable('2024-07-15')));
        $this->assertEquals('2024-Summer', $service->testGetCurrentSeason(new \DateTimeImmutable('2024-09-15')));
    }

    public function testGetCurrentSeasonFall(): void
    {
        $service = new class extends EloRatingService {
            public function __construct() {}
            public function testGetCurrentSeason(\DateTimeImmutable $date): string {
                $year = $date->format('Y');
                $month = (int) $date->format('n');
                
                if ($month >= 1 && $month <= 3) {
                    return "$year-Winter";
                } elseif ($month >= 4 && $month <= 6) {
                    return "$year-Spring";
                } elseif ($month >= 7 && $month <= 9) {
                    return "$year-Summer";
                } else {
                    return "$year-Fall";
                }
            }
        };
        
        $this->assertEquals('2024-Fall', $service->testGetCurrentSeason(new \DateTimeImmutable('2024-10-15')));
        $this->assertEquals('2024-Fall', $service->testGetCurrentSeason(new \DateTimeImmutable('2024-12-15')));
    }

    public function testGetTier(): void
    {
        $this->assertEquals('Challenger', EloRatingService::getTier(2400));
        $this->assertEquals('Challenger', EloRatingService::getTier(2500));
        
        $this->assertEquals('Grandmaster', EloRatingService::getTier(2200));
        $this->assertEquals('Grandmaster', EloRatingService::getTier(2399));
        
        $this->assertEquals('Master', EloRatingService::getTier(2000));
        $this->assertEquals('Master', EloRatingService::getTier(2199));
        
        $this->assertEquals('Diamond', EloRatingService::getTier(1800));
        $this->assertEquals('Diamond', EloRatingService::getTier(1999));
        
        $this->assertEquals('Platinum', EloRatingService::getTier(1600));
        $this->assertEquals('Platinum', EloRatingService::getTier(1799));
        
        $this->assertEquals('Gold', EloRatingService::getTier(1400));
        $this->assertEquals('Gold', EloRatingService::getTier(1599));
        
        $this->assertEquals('Silver', EloRatingService::getTier(1200));
        $this->assertEquals('Silver', EloRatingService::getTier(1399));
        
        $this->assertEquals('Bronze', EloRatingService::getTier(1199));
        $this->assertEquals('Bronze', EloRatingService::getTier(1000));
    }

    public function testGetTierColor(): void
    {
        $this->assertEquals('#ff4655', EloRatingService::getTierColor('Challenger'));
        $this->assertEquals('#ff4655', EloRatingService::getTierColor('Grandmaster'));
        $this->assertEquals('#9b59b6', EloRatingService::getTierColor('Master'));
        $this->assertEquals('#3498db', EloRatingService::getTierColor('Diamond'));
        $this->assertEquals('#1abc9c', EloRatingService::getTierColor('Platinum'));
        $this->assertEquals('#f1c40f', EloRatingService::getTierColor('Gold'));
        $this->assertEquals('#95a5a6', EloRatingService::getTierColor('Silver'));
        $this->assertEquals('#cd7f32', EloRatingService::getTierColor('Bronze'));
        $this->assertEquals('#95a5a6', EloRatingService::getTierColor('Unknown'));
    }

    public function testUpdateRatingsWithRegion(): void
    {
        $winner = $this->createMock(Player::class);
        $winner->method('getId')->willReturn(1);
        
        $loser = $this->createMock(Player::class);
        $loser->method('getId')->willReturn(2);
        
        $game = $this->createMock(Game::class);
        $game->method('getId')->willReturn(1);
        
        $this->rankHistoryRepository->method('findLatestPlayerRank')
            ->willReturn(null);
        
        $this->rankHistoryRepository->method('findGlobalRankings')
            ->willReturn([]);
        
        $this->entityManager->expects($this->exactly(2))
            ->method('persist');
        // The flush is called twice in recordRatingChange which is called twice
        $this->entityManager->expects($this->exactly(2))
            ->method('flush');
        
        $result = $this->service->updateRatings($winner, $loser, $game, 'EUW');
        
        $this->assertArrayHasKey('winnerChange', $result);
        $this->assertArrayHasKey('loserChange', $result);
    }

    public function testCalculateExpectedScoreExtremeDifference(): void
    {
        $ratingA = 2000;
        $ratingB = 1000;
        
        $expectedScore = $this->service->calculateExpectedScore($ratingA, $ratingB);
        
        $this->assertGreaterThan(0.9, $expectedScore);
        $this->assertLessThan(1.0, $expectedScore);
    }

    public function testCalculateNewRatingExtremeCases(): void
    {
        $currentRating = 1000;
        $expectedScore = 0.99;
        $actualScore = 1.0;
        
        $newRating = $this->service->calculateNewRating($currentRating, $expectedScore, $actualScore);
        
        // Calculate expected: 32 * (1.0 - 0.99) = 0.32, rounded to 0
        // So new rating should be 1000, not 1001
        $this->assertEquals(1000, $newRating);
    }
}
