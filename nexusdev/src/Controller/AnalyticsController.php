<?php

namespace App\Controller;

use App\Entity\Game;
use App\Entity\Player;
use App\Repository\GameMatchRepository;
use App\Repository\GameRepository;
use App\Repository\MatchPlayerRepository;
use App\Repository\PlayerRepository;
use App\Repository\StatisticRepository;
use App\Service\EloRatingService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/analytics')]
class AnalyticsController extends AbstractController
{
    public function __construct(
        private PlayerRepository $playerRepository,
        private GameRepository $gameRepository,
        private GameMatchRepository $gameMatchRepository,
        private MatchPlayerRepository $matchPlayerRepository,
        private StatisticRepository $statisticRepository,
        private EloRatingService $eloRatingService
    ) {}

    #[Route('/', name: 'app_analytics_index')]
    public function index(Request $request): Response
    {
        $games = $this->gameRepository->findAll();
        $selectedGameId = $request->query->getInt('game', $games[0]->getId() ?? 0);
        $selectedGame = $this->gameRepository->find($selectedGameId);

        // Get top players for featured section (using Statistic entity)
        $topPlayers = $this->statisticRepository->findTopPlayersByGame($selectedGameId, 5);

        // Get recent matches for the game
        $recentMatches = $this->gameMatchRepository->findByGame($selectedGameId, 10);

        return $this->render('analytics/index.html.twig', [
            'games' => $games,
            'selectedGame' => $selectedGame,
            'topPlayers' => $topPlayers,
            'recentMatches' => $recentMatches
        ]);
    }

    #[Route('/player/{playerId}', name: 'app_analytics_player')]
    public function playerAnalytics(int $playerId, Request $request): Response
    {
        $player = $this->playerRepository->find($playerId);
        if (!$player) {
            throw $this->createNotFoundException('Player not found');
        }

        $gameId = $request->query->getInt('game', $player->getGame()->getId());
        $game = $this->gameRepository->find($gameId);

        // Date ranges
        $now = new \DateTimeImmutable();
        $lastMonth = $now->modify('-1 month');
        $last3Months = $now->modify('-3 months');
        $last6Months = $now->modify('-6 months');

        // Get performance stats
        $monthlyStats = $this->matchPlayerRepository->findPlayerPerformanceStats(
            $playerId,
            $lastMonth,
            $now
        );

        $quarterlyStats = $this->matchPlayerRepository->findPlayerPerformanceStats(
            $playerId,
            $last3Months,
            $now
        );

        // Get rank history for charts (using Statistic entity)
        $stat = $this->statisticRepository->findPlayerStats($playerId, $gameId);
        $chartData = [];
        if ($stat) {
            $chartData[] = [
                'date' => $stat->getCreatedAt()->format('Y-m-d'),
                'elo' => $player->getScore(),
                'winRate' => $stat->getWinRate()
            ];
        }

        // Get recent matches
        $recentMatches = $this->gameMatchRepository->findByPlayer($playerId, 20);

        // Get current tier based on score
        $currentTier = $this->getTierFromScore($player->getScore());

        // Get all games for the selector
        $games = $this->gameRepository->findAll();
        $selectedGame = $game;

        // Get team statistics if player has a team
        $teamStats = null;
        if ($player->getTeam()) {
            $teamStat = $this->statisticRepository->findByTeam($player->getTeam()->getId());
            if ($teamStat) {
                $totalMatches = 0;
                $totalWins = 0;
                foreach ($teamStat as $stat) {
                    $totalMatches += $stat->getMatchesPlayed();
                    $totalWins += $stat->getWins();
                }
                $teamStats = [
                    'matchesPlayed' => $totalMatches,
                    'wins' => $totalWins,
                    'winRate' => $totalMatches > 0 ? round(($totalWins / $totalMatches) * 100, 2) : 0,
                    'playerCount' => count($teamStat)
                ];
            }
        }

        return $this->render('analytics/player.html.twig', [
            'player' => $player,
            'game' => $game,
            'games' => $games,
            'selectedGame' => $selectedGame,
            'playerStat' => $stat,
            'teamStats' => $teamStats,
            'monthlyStats' => $monthlyStats,
            'quarterlyStats' => $quarterlyStats,
            'chartData' => $chartData,
            'recentMatches' => $recentMatches,
            'currentTier' => $currentTier,
            'latestElo' => $player->getScore()
        ]);
    }

    #[Route('/player/{playerId}/trends', name: 'app_analytics_trends')]
    public function playerTrends(int $playerId, Request $request): Response
    {
        $gameId = $request->query->getInt('game');
        $startDate = $request->query->get('start');
        $endDate = $request->query->get('end');

        $start = $startDate ? new \DateTimeImmutable($startDate) : new \DateTimeImmutable('-3 months');
        $end = $endDate ? new \DateTimeImmutable($endDate) : new \DateTimeImmutable();

        // Get performance data grouped by week
        $matches = $this->matchPlayerRepository->findByPlayer($playerId, 100);
        
        $weeklyData = [];
        foreach ($matches as $matchPlayer) {
            $match = $matchPlayer->getGameMatch();
            if ($match->getMatchDate() >= $start && $match->getMatchDate() <= $end) {
                $weekKey = $match->getMatchDate()->format('Y-W');
                if (!isset($weeklyData[$weekKey])) {
                    $weeklyData[$weekKey] = [
                        'week' => $weekKey,
                        'kills' => 0,
                        'deaths' => 0,
                        'assists' => 0,
                        'matches' => 0,
                        'wins' => 0
                    ];
                }
                $weeklyData[$weekKey]['kills'] += $matchPlayer->getKills();
                $weeklyData[$weekKey]['deaths'] += $matchPlayer->getDeaths();
                $weeklyData[$weekKey]['assists'] += $matchPlayer->getAssists();
                $weeklyData[$weekKey]['matches']++;
                if ($matchPlayer->isWinner()) {
                    $weeklyData[$weekKey]['wins']++;
                }
            }
        }

        // Sort by week
        ksort($weeklyData);

        return $this->json([
            'trends' => array_values($weeklyData)
        ]);
    }

    #[Route('/player/{playerId}/heatmap', name: 'app_analytics_heatmap')]
    public function playerHeatmap(int $playerId, Request $request): Response
    {
        $gameId = $request->query->getInt('game');
        
        $heatmapData = $this->matchPlayerRepository->findHeatMapData($playerId, $gameId);

        return $this->json([
            'heatmap' => $heatmapData
        ]);
    }

    #[Route('/team/{teamId}/synergy', name: 'app_analytics_team_synergy')]
    public function teamSynergy(int $teamId, Request $request): Response
    {
        $gameId = $request->query->getInt('game');
        
        $synergyStats = $this->matchPlayerRepository->findTeamSynergyStats($teamId, $gameId);

        // Get all games for the selector
        $games = $this->gameRepository->findAll();
        $selectedGame = $gameId ? $this->gameRepository->find($gameId) : null;

        return $this->render('analytics/team_synergy.html.twig', [
            'synergyStats' => $synergyStats,
            'games' => $games,
            'selectedGame' => $selectedGame
        ]);
    }

    #[Route('/export/player/{playerId}', name: 'app_analytics_export')]
    public function exportPlayerStats(int $playerId, Request $request): Response
    {
        $format = $request->query->get('format', 'json'); // json, csv, pdf
        $player = $this->playerRepository->find($playerId);
        
        if (!$player) {
            throw $this->createNotFoundException('Player not found');
        }

        $gameId = $request->query->getInt('game', $player->getGame()->getId());
        
        // Get all stats (using Statistic entity)
        $stat = $this->statisticRepository->findPlayerStats($playerId, $gameId);
        $matches = $this->matchPlayerRepository->findByPlayer($playerId, 100);
        
        $data = [
            'player' => [
                'id' => $player->getId(),
                'nickname' => $player->getNickname(),
                'game' => $player->getGame()->getName()
            ],
            'statistics' => $stat ? [
                'matchesPlayed' => $stat->getMatchesPlayed(),
                'wins' => $stat->getWins(),
                'losses' => $stat->getLosses(),
                'winRate' => $stat->getWinRate(),
                'kills' => $stat->getKills(),
                'deaths' => $stat->getDeaths(),
                'assists' => $stat->getAssists(),
                'kda' => $stat->getDeaths() > 0 ? round(($stat->getKills() + $stat->getAssists()) / $stat->getDeaths(), 2) : $stat->getKills() + $stat->getAssists()
            ] : null,
            'matches' => []
        ];

        foreach ($matches as $matchPlayer) {
            $match = $matchPlayer->getGameMatch();
            $data['matches'][] = [
                'date' => $match->getMatchDate()?->format('Y-m-d'),
                'map' => $match->getMap(),
                'kills' => $matchPlayer->getKills(),
                'deaths' => $matchPlayer->getDeaths(),
                'assists' => $matchPlayer->getAssists(),
                'kda' => $matchPlayer->getDeaths() > 0 
                    ? round(($matchPlayer->getKills() + $matchPlayer->getAssists()) / $matchPlayer->getDeaths(), 2) 
                    : $matchPlayer->getKills() + $matchPlayer->getAssists(),
                'result' => $matchPlayer->isWinner() ? 'Win' : 'Loss',
                'eloChange' => $matchPlayer->getEloChange()
            ];
        }

        if ($format === 'json') {
            return $this->json($data);
        }

        if ($format === 'csv') {
            $csv = $this->generateCsv($data);
            $response = new Response($csv);
            $response->headers->set('Content-Type', 'text/csv');
            $response->headers->set('Content-Disposition', 'attachment; filename="' . $player->getNickname() . '_stats.csv"');
            return $response;
        }

        // PDF would require a library like TCPDF or DomPDF - returning JSON for now
        return $this->json($data);
    }

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

    private function generateCsv(array $data): string
    {
        $output = fopen('php://temp', 'r+');
        
        // Player info
        fputcsv($output, ['Player Statistics Export']);
        fputcsv($output, ['Player', $data['player']['nickname']]);
        fputcsv($output, ['Game', $data['player']['game']]);
        fputcsv($output, []);
        
        // Statistics
        if ($data['statistics']) {
            fputcsv($output, ['Statistics Summary']);
            fputcsv($output, ['Matches', $data['statistics']['matchesPlayed']]);
            fputcsv($output, ['Wins', $data['statistics']['wins']]);
            fputcsv($output, ['Losses', $data['statistics']['losses']]);
            fputcsv($output, ['Win Rate', $data['statistics']['winRate'] . '%']);
            fputcsv($output, ['Kills', $data['statistics']['kills']]);
            fputcsv($output, ['Deaths', $data['statistics']['deaths']]);
            fputcsv($output, ['Assists', $data['statistics']['assists']]);
            fputcsv($output, ['KDA', $data['statistics']['kda']]);
            fputcsv($output, []);
        }
        
        // Matches
        fputcsv($output, ['Match History']);
        fputcsv($output, ['Date', 'Map', 'Kills', 'Deaths', 'Assists', 'KDA', 'Result']);
        foreach ($data['matches'] as $match) {
            fputcsv($output, [
                $match['date'],
                $match['map'],
                $match['kills'],
                $match['deaths'],
                $match['assists'],
                $match['kda'],
                $match['result']
            ]);
        }
        
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        
        return $csv;
    }
}
