<?php

namespace App\Controller;

use App\Entity\Achievement;
use App\Entity\PlayerAchievement;
use App\Repository\AchievementRepository;
use App\Repository\PlayerAchievementRepository;
use App\Repository\PlayerRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/achievements')]
class AchievementController extends AbstractController
{
    public function __construct(
        private AchievementRepository $achievementRepository,
        private PlayerAchievementRepository $playerAchievementRepository,
        private PlayerRepository $playerRepository
    ) {}

    #[Route('/', name: 'app_achievements_index')]
    public function index(Request $request): Response
    {
        $achievements = $this->achievementRepository->findAll();
        
        return $this->render('achievement/index.html.twig', [
            'achievements' => $achievements
        ]);
    }

    #[Route('/player/{playerId}', name: 'app_achievements_player')]
    public function playerAchievements(int $playerId): Response
    {
        $player = $this->playerRepository->find($playerId);
        if (!$player) {
            throw $this->createNotFoundException('Player not found');
        }

        $unlockedAchievements = $this->playerAchievementRepository->findUnlockedByPlayer($playerId);
        $allAchievements = $this->achievementRepository->findAll();
        
        $totalPoints = $this->playerAchievementRepository->getTotalPointsByPlayer($playerId);

        return $this->render('achievement/player.html.twig', [
            'player' => $player,
            'unlockedAchievements' => $unlockedAchievements,
            'allAchievements' => $allAchievements,
            'totalPoints' => $totalPoints
        ]);
    }

    #[Route('/check/{playerId}', name: 'app_achievements_check', methods: ['POST'])]
    public function checkAchievements(int $playerId): JsonResponse
    {
        $player = $this->playerRepository->find($playerId);
        if (!$player) {
            return $this->json(['error' => 'Player not found'], 404);
        }

        $achievements = $this->achievementRepository->findAll();
        $newlyUnlocked = [];

        foreach ($achievements as $achievement) {
            $playerAchievement = $this->playerAchievementRepository->findPlayerAchievement(
                $playerId,
                $achievement->getId()
            );

            if (!$playerAchievement) {
                $playerAchievement = new PlayerAchievement();
                $playerAchievement->setPlayer($player);
                $playerAchievement->setAchievement($achievement);
            }

            if (!$playerAchievement->isUnlocked()) {
                // Check if achievement should be unlocked
                $shouldUnlock = $this->checkAchievementCriteria($player, $achievement);
                
                if ($shouldUnlock) {
                    $playerAchievement->setIsUnlocked(true);
                    $playerAchievement->setUnlockedAt(new \DateTimeImmutable());
                    $this->playerAchievementRepository->save($playerAchievement);
                    $newlyUnlocked[] = $achievement->getName();
                }
            }
        }

        return $this->json([
            'newlyUnlocked' => $newlyUnlocked,
            'count' => count($newlyUnlocked)
        ]);
    }

    private function checkAchievementCriteria($player, Achievement $achievement): bool
    {
        // This is a simplified check - you would expand this based on achievement types
        $statistics = $player->getStatistics();
        
        switch ($achievement->getType()) {
            case 'matches_played':
                foreach ($statistics as $stat) {
                    if ($stat->getMatchesPlayed() >= $achievement->getRequiredValue()) {
                        return true;
                    }
                }
                break;
                
            case 'wins':
                foreach ($statistics as $stat) {
                    if ($stat->getWins() >= $achievement->getRequiredValue()) {
                        return true;
                    }
                }
                break;
                
            case 'kills':
                foreach ($statistics as $stat) {
                    if ($stat->getKills() >= $achievement->getRequiredValue()) {
                        return true;
                    }
                }
                break;
                
            case 'score':
                if ($player->getScore() >= $achievement->getRequiredValue()) {
                    return true;
                }
                break;
                
            case 'pro_status':
                if ($player->isPro()) {
                    return true;
                }
                break;
        }
        
        return false;
    }
}
