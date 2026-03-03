<?php

namespace App\Controller;

use App\Entity\Game;
use App\Repository\StatisticRepository;
use App\Repository\GameRepository;
use App\Repository\OrganizationRepository;
use App\Repository\TeamRepository;
use App\Service\EloRatingService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/leaderboard')]
class LeaderboardController extends AbstractController
{
    public function __construct(
        private StatisticRepository $statisticRepository,
        private GameRepository $gameRepository,
        private OrganizationRepository $organizationRepository,
        private TeamRepository $teamRepository,
        private EloRatingService $eloRatingService
    ) {}

    #[Route('/', name: 'app_leaderboard_index')]
    public function index(Request $request): Response
    {
        $games = $this->gameRepository->findAll();
        $selectedGameId = $request->query->getInt('game', $games[0]->getId() ?? 0);
        $season = $request->query->get('season', $this->eloRatingService->getCurrentSeason());

        $selectedGame = $this->gameRepository->find($selectedGameId);
        
        return $this->render('leaderboard/index.html.twig', [
            'games' => $games,
            'selectedGame' => $selectedGame,
            'season' => $season
        ]);
    }

    #[Route('/global/{gameId}', name: 'app_leaderboard_global')]
    public function globalRankings(int $gameId, Request $request): Response
    {
        $season = $request->query->get('season', $this->eloRatingService->getCurrentSeason());
        
        // Get statistics and sort by win rate (using existing Statistic entity)
        $statistics = $this->statisticRepository->findTopPlayersByGame($gameId, 100);
        $game = $this->gameRepository->find($gameId);

        // Process rankings using player score as ELO equivalent
        $processedRankings = [];
        foreach ($statistics as $index => $stat) {
            $player = $stat->getPlayer();
            if (!$player) continue;
            
            // Use player score as base for tier calculation
            $score = $player->getScore();
            $data = [
                'rank' => $index + 1,
                'player' => $player,
                'statistic' => $stat,
                'eloRating' => $score, // Using score as ELO equivalent
                'winRate' => $stat->getWinRate(),
                'matches' => $stat->getMatchesPlayed(),
                'tier' => $this->getTierFromScore($score),
                'tierColor' => $this->getTierColorFromScore($score)
            ];
            $processedRankings[] = $data;
        }

        $games = $this->gameRepository->findAll();

        return $this->render('leaderboard/global.html.twig', [
            'rankings' => $processedRankings,
            'game' => $game,
            'games' => $games,
            'selectedGame' => $game,
            'season' => $season
        ]);
    }

    #[Route('/regional/{gameId}', name: 'app_leaderboard_regional')]
    public function regionalRankings(int $gameId, Request $request): Response
    {
        $region = $request->query->get('region', 'NA');
        $season = $request->query->get('season', $this->eloRatingService->getCurrentSeason());
        
        // Get all statistics and filter by player nationality as region
        $allStats = $this->statisticRepository->findTopPlayersByGame($gameId, 200);
        $game = $this->gameRepository->find($gameId);
        
        // Filter by player nationality (treating nationality as region)
        $filteredStats = array_filter($allStats, function($stat) use ($region) {
            $player = $stat->getPlayer();
            return $player && $player->getNationality() === $region;
        });

        $regions = ['NA', 'EU', 'KR', 'CN', 'BR', 'JP', 'TR'];

        $processedRankings = [];
        $index = 0;
        foreach ($filteredStats as $stat) {
            $player = $stat->getPlayer();
            $score = $player->getScore();
            $data = [
                'rank' => ++$index,
                'player' => $player,
                'statistic' => $stat,
                'eloRating' => $score,
                'winRate' => $stat->getWinRate(),
                'matches' => $stat->getMatchesPlayed(),
                'tier' => $this->getTierFromScore($score),
                'tierColor' => $this->getTierColorFromScore($score)
            ];
            $processedRankings[] = $data;
        }

        $games = $this->gameRepository->findAll();

        return $this->render('leaderboard/regional.html.twig', [
            'rankings' => $processedRankings,
            'game' => $game,
            'games' => $games,
            'selectedGame' => $game,
            'region' => $region,
            'regions' => $regions,
            'season' => $season
        ]);
    }

    #[Route('/organizations/{gameId}', name: 'app_leaderboard_organizations')]
    public function organizationRankings(int $gameId, Request $request): Response
    {
        $season = $request->query->get('season', $this->eloRatingService->getCurrentSeason());
        
        // Get organizations with teams for this game
        $organizations = $this->organizationRepository->findWithTeamsByGame($gameId);
        $game = $this->gameRepository->find($gameId);

        // Calculate organization rankings based on team statistics
        $orgRankings = [];
        foreach ($organizations as $org) {
            $totalScore = 0;
            $playerCount = 0;
            $totalWinRate = 0;
            
            foreach ($org->getTeams() as $team) {
                if ($team->getGame()->getId() === $gameId) {
                    // Get team stats
                    $teamStats = $this->statisticRepository->findByTeam($team->getId());
                    foreach ($teamStats as $stat) {
                        $player = $stat->getPlayer();
                        if ($player) {
                            $totalScore += $player->getScore();
                            $totalWinRate += (float) $stat->getWinRate();
                            $playerCount++;
                        }
                    }
                }
            }

            if ($playerCount > 0) {
                $avgScore = (int) ($totalScore / $playerCount);
                $avgWinRate = round($totalWinRate / $playerCount, 2);
                $orgRankings[] = [
                    'organization' => $org,
                    'averageScore' => $avgScore,
                    'averageWinRate' => $avgWinRate,
                    'playerCount' => $playerCount,
                    'tier' => $this->getTierFromScore($avgScore),
                    'tierColor' => $this->getTierColorFromScore($avgScore)
                ];
            }
        }

        // Sort by average score
        usort($orgRankings, fn($a, $b) => $b['averageScore'] <=> $a['averageScore']);

        // Add rank numbers
        foreach ($orgRankings as $index => &$ranking) {
            $ranking['rank'] = $index + 1;
        }

        $games = $this->gameRepository->findAll();

        return $this->render('leaderboard/organizations.html.twig', [
            'rankings' => $orgRankings,
            'game' => $game,
            'games' => $games,
            'selectedGame' => $game,
            'season' => $season
        ]);
    }

    #[Route('/player/{playerId}/history', name: 'app_leaderboard_player_history')]
    public function playerRankHistory(int $playerId, Request $request): Response
    {
        $gameId = $request->query->getInt('game');
        
        // Get player stats for history (using Statistic entity)
        $stat = $this->statisticRepository->findPlayerStats($playerId, $gameId);
        
        // Return current stats as history data
        $chartData = [];
        if ($stat) {
            $chartData[] = [
                'date' => $stat->getCreatedAt()->format('Y-m-d'),
                'matches' => $stat->getMatchesPlayed(),
                'wins' => $stat->getWins(),
                'winRate' => $stat->getWinRate()
            ];
        }

        $player = $stat?->getPlayer();
        $currentScore = $player ? $player->getScore() : 0;

        return $this->json([
            'history' => $chartData,
            'currentTier' => $this->getTierFromScore($currentScore)
        ]);
    }

    #[Route('/seasons', name: 'app_leaderboard_seasons')]
    public function getSeasons(): Response
    {
        $currentYear = (int) date('Y');
        $seasons = [];
        
        // Generate seasons for current and previous year
        for ($year = $currentYear - 1; $year <= $currentYear + 1; $year++) {
            $seasons[] = "$year-Winter";
            $seasons[] = "$year-Spring";
            $seasons[] = "$year-Summer";
            $seasons[] = "$year-Fall";
        }

        return $this->json(['seasons' => $seasons]);
    }

    // Helper methods for tier calculation based on score
    private function getTierFromScore(int $score): string
    {
        if ($score >= 2000) return 'Challenger';
        if ($score >= 1800) return 'Grandmaster';
        if ($score >= 1600) return 'Master';
        if ($score >= 1400) return 'Diamond';
        if ($score >= 1200) return 'Platinum';
        if ($score >= 1000) return 'Gold';
        if ($score >= 800) return 'Silver';
        return 'Bronze';
    }

    private function getTierColorFromScore(int $score): string
    {
        if ($score >= 2000) return '#ef4444'; // Challenger - Red
        if ($score >= 1800) return '#f97316'; // Grandmaster - Orange
        if ($score >= 1600) return '#8b5cf6'; // Master - Purple
        if ($score >= 1400) return '#06b6d4'; // Diamond - Cyan
        if ($score >= 1200) return '#10b981'; // Platinum - Emerald
        if ($score >= 1000) return '#eab308'; // Gold - Yellow
        if ($score >= 800) return '#64748b'; // Silver - Slate
        return '#78350f'; // Bronze - Brown
    }
}
