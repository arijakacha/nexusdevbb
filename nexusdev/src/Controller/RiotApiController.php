<?php

namespace App\Controller;

use App\Service\RiotStatsSyncService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/player/riot')]
#[IsGranted('ROLE_USER')]
class RiotApiController extends AbstractController
{
    private RiotStatsSyncService $riotStatsSyncService;
    private EntityManagerInterface $entityManager;

    public function __construct(
        RiotStatsSyncService $riotStatsSyncService,
        EntityManagerInterface $entityManager
    ) {
        $this->riotStatsSyncService = $riotStatsSyncService;
        $this->entityManager = $entityManager;
    }

    #[Route('/sync-stats', name: 'api_riot_sync_stats', methods: ['POST'])]
    public function syncStats(Request $request): JsonResponse
    {
        try {
            $user = $this->getUser();
            $player = $user?->getPlayer();

            if (!$player) {
                return $this->json([
                    'success' => false,
                    'error' => 'No player profile found',
                ], 400);
            }

            if (!$user->getRiotSummonerName()) {
                return $this->json([
                    'success' => false,
                    'error' => 'Riot summoner name not configured',
                ], 400);
            }

            $region = $request->request->get('region', $user->getRiotRegion());

            $result = $this->riotStatsSyncService->syncPlayerStats($player, $region);

            // Get the League of Legends statistic specifically
            $statistics = $player->getStatistics();
            $statistic = null;
            foreach ($statistics as $s) {
                if ($s->getGame() && $s->getGame()->getName() === 'League of Legends') {
                    $statistic = $s;
                    break;
                }
            }

            if ($result['success']) {
                return $this->json([
                    'success' => true,
                    'message' => 'Stats synced successfully',
                    'synced_at' => $user->getRiotLastSyncAt()?->format('Y-m-d H:i:s'),
                    'rank' => $result['rank_tier'] . ' ' . $result['rank_division'],
                    'lp' => $result['lp'],
                    'stats' => $statistic ? [
                        'rank' => $statistic->getRankTier() . ' ' . $statistic->getRankDivision(),
                        'league_points' => $statistic->getLeaguePoints(),
                        'matches_played' => $statistic->getMatchesPlayed(),
                        'wins' => $statistic->getWins(),
                        'losses' => $statistic->getLosses(),
                        'win_rate' => $statistic->getWinRate() . '%',
                    ] : null,
                ]);
            }

            return $this->json([
                'success' => false,
                'error' => $result['error'] ?? 'Failed to sync stats',
                'debug' => [
                    'summoner' => $user->getRiotSummonerName(),
                    'region' => $region,
                ],
            ], 500);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => 'Exception: ' . $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ], 500);
        }
    }

    #[Route('/update-summoner', name: 'api_riot_update_summoner', methods: ['POST'])]
    public function updateSummoner(Request $request): JsonResponse
    {
        $user = $this->getUser();
        $summonerName = $request->request->get('summoner_name');
        $region = $request->request->get('region', 'euw1');

        if (!$summonerName) {
            return $this->json([
                'success' => false,
                'error' => 'Summoner name is required',
            ], 400);
        }

        $user->setRiotSummonerName($summonerName);
        $user->setRiotRegion($region);
        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Summoner name updated',
            'summoner_name' => $summonerName,
            'region' => $region,
        ]);
    }
}
