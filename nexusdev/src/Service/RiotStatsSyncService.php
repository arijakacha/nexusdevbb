<?php

namespace App\Service;

use App\Entity\Game;
use App\Entity\Player;
use App\Entity\Statistic;
use App\Repository\GameRepository;
use App\Repository\StatisticRepository;
use Doctrine\ORM\EntityManagerInterface;

class RiotStatsSyncService
{
    private RiotApiService $riotApiService;
    private EntityManagerInterface $entityManager;
    private StatisticRepository $statisticRepository;
    private GameRepository $gameRepository;
    private ?Game $leagueOfLegends = null;

    public function __construct(
        RiotApiService $riotApiService,
        EntityManagerInterface $entityManager,
        StatisticRepository $statisticRepository,
        GameRepository $gameRepository
    ) {
        $this->riotApiService = $riotApiService;
        $this->entityManager = $entityManager;
        $this->statisticRepository = $statisticRepository;
        $this->gameRepository = $gameRepository;
    }

    private function getLeagueOfLegendsGame(): ?Game
    {
        if ($this->leagueOfLegends === null) {
            $this->leagueOfLegends = $this->gameRepository->findOneBy(['name' => 'League of Legends']);
        }

        return $this->leagueOfLegends;
    }

    public function syncPlayerStats(Player $player, ?string $region = null): array
    {
        $user = $player->getUser();

        if (!$user) {
            $msg = "No user for player " . $player->getId();
            error_log("RiotSync: $msg");
            return ['success' => false, 'error' => $msg];
        }

        $summonerName = $user->getRiotSummonerName();
        $region = $region ?? $user->getRiotRegion() ?? 'euw1';

        if (!$summonerName) {
            $msg = "No summoner name for user " . $user->getId();
            error_log("RiotSync: $msg");
            return ['success' => false, 'error' => $msg];
        }

        // Parse summoner name and tag line
        $tagLine = 'BWS'; // default tag
        $gameName = trim($summonerName);

        if (str_contains($summonerName, '#')) {
            [$gameName, $tagLine] = explode('#', $summonerName, 2);
            $gameName = trim($gameName);
            $tagLine = trim($tagLine) ?: 'BWS';
        }

        error_log("RiotSync: Looking up account - GameName: '$gameName', TagLine: '$tagLine', Region: '$region'");

        // Get account by Riot ID
        $account = $this->riotApiService->getAccountByRiotId($gameName, $tagLine, $region);

        if (!$account) {
            $msg = "Failed to get account for '$gameName#$tagLine' - check if summoner exists";
            error_log("RiotSync: $msg");
            return ['success' => false, 'error' => $msg];
        }

        if (isset($account['error'])) {
            $msg = "Riot API error: " . $account['error'];
            error_log("RiotSync: $msg");
            return ['success' => false, 'error' => $msg];
        }

        $puuid = $account['puuid'] ?? null;

        if (!$puuid) {
            $msg = "No PUUID in account response";
            error_log("RiotSync: $msg");
            return ['success' => false, 'error' => $msg];
        }

        error_log("RiotSync: Got PUUID: $puuid");

        // Get summoner data
        $summoner = $this->riotApiService->getSummonerByPuuid($puuid, $region);

        if ($summoner && !isset($summoner['error'])) {
            $user->setRiotSummonerId($summoner['id'] ?? null);
        } else if (isset($summoner['error'])) {
            error_log("RiotSync: Summoner error: " . $summoner['error']);
        }

        // Get or create statistic entity FIRST
        $game = $this->getLeagueOfLegendsGame();

        if (!$game) {
            return ['success' => false, 'error' => 'League of Legends game not found in database'];
        }

        $statistic = $this->statisticRepository->findOneBy([
            'player' => $player,
            'game' => $game,
        ]);

        if (!$statistic) {
            $statistic = new Statistic();
            $statistic->setPlayer($player);
            $statistic->setGame($game);
            $statistic->setTeam($player->getTeam());
            $this->entityManager->persist($statistic);
        }

        // Get ranked data
        $summonerId = $user->getRiotSummonerId();
        $rankData = null;

        if ($summonerId) {
            $rankedEntries = $this->riotApiService->getRankedEntries($summonerId, $region);
            if ($rankedEntries && !isset($rankedEntries['error'])) {
                $rankData = $this->riotApiService->getSoloQueueRank($rankedEntries);
                error_log("RiotSync: Got " . count($rankedEntries) . " ranked entries");
            } else if (isset($rankedEntries['error'])) {
                error_log("RiotSync: Ranked entries error: " . $rankedEntries['error']);
            }
        }

        // Get match stats
        $matchStats = $this->riotApiService->calculateStatsFromMatches($puuid, 20, $region);

        // Get recent match details for display
        $recentMatches = $this->riotApiService->getRecentMatchDetails($puuid, 2, $region);
        if ($recentMatches) {
            $user->setRecentMatches($recentMatches);
        }

        // Update user with Riot data
        $user->setRiotPuuid($puuid);
        $user->setRiotRegion($region);

        // Update stats from match data
        if ($matchStats) {
            $statistic->setMatchesPlayed($matchStats['matchesPlayed']);
            $statistic->setWins($matchStats['wins']);
            $statistic->setLosses($matchStats['losses']);
            $statistic->setKills($matchStats['kills']);
            $statistic->setDeaths($matchStats['deaths']);
            $statistic->setAssists($matchStats['assists']);
            $statistic->setWinRate((string) $matchStats['winRate']);
        }

        // Update rank data
        if ($rankData) {
            error_log("RiotSync: Saving rank - Tier: {$rankData['tier']}, Rank: {$rankData['rank']}, LP: {$rankData['leaguePoints']}");
            $statistic->setRankTier($rankData['tier']);
            $statistic->setRankDivision($rankData['rank']);
            $statistic->setLeaguePoints($rankData['leaguePoints']);
        } else {
            error_log("RiotSync: No rank data found - player might be unranked");
        }

        $statistic->setUpdatedAt(new \DateTimeImmutable());
        $user->setRiotLastSyncAt(new \DateTimeImmutable());

        $this->entityManager->persist($statistic);
        $this->entityManager->flush();

        error_log("RiotSync: Stats saved - RankTier: {$statistic->getRankTier()}, Division: {$statistic->getRankDivision()}");

        return [
            'success' => true,
            'rank_tier' => $statistic->getRankTier(),
            'rank_division' => $statistic->getRankDivision(),
            'lp' => $statistic->getLeaguePoints(),
        ];
    }

    public function syncAllPlayers(): array
    {
        $playerRepository = $this->entityManager->getRepository(Player::class);
        $players = $playerRepository->findAll();

        $results = [
            'success' => 0,
            'failed' => 0,
            'skipped' => 0,
        ];

        foreach ($players as $player) {
            $user = $player->getUser();

            if (!$user || !$user->getRiotSummonerName()) {
                $results['skipped']++;
                continue;
            }

            $success = $this->syncPlayerStats($player);

            if ($success) {
                $results['success']++;
            } else {
                $results['failed']++;
            }
        }

        return $results;
    }
}
