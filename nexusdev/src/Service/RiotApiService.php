<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class RiotApiService
{
    private HttpClientInterface $httpClient;
    private string $apiKey;
    private string $region;

    private const RIOT_API_BASE_URL = 'https://{region}.api.riotgames.com';
    private const RIOT_API_ACCOUNT_URL = 'https://{region}.api.riotgames.com/riot/account/v1/accounts/by-riot-id/{gameName}/{tagLine}';
    private const RIOT_API_SUMMONER_URL = 'https://{region}.api.riotgames.com/lol/summoner/v4/summoners/by-puuid/{puuid}';
    private const RIOT_API_RANKED_URL = 'https://{region}.api.riotgames.com/lol/league/v4/entries/by-summoner/{summonerId}';
    private const RIOT_API_MATCHES_URL = 'https://{region}.api.riotgames.com/lol/match/v5/matches/by-puuid/{puuid}/ids';
    private const RIOT_API_MATCH_URL = 'https://{region}.api.riotgames.com/lol/match/v5/matches/{matchId}';

    private array $regionMapping = [
        'euw' => 'euw1',
        'eune' => 'eun1',
        'na' => 'na1',
        'br' => 'br1',
        'lan' => 'la1',
        'las' => 'la2',
        'oce' => 'oc1',
        'ru' => 'ru',
        'tr' => 'tr1',
        'jp' => 'jp1',
        'kr' => 'kr',
    ];

    private array $matchRegionMapping = [
        'euw1' => 'europe',
        'eun1' => 'europe',
        'na1' => 'americas',
        'br1' => 'americas',
        'la1' => 'americas',
        'la2' => 'americas',
        'oc1' => 'sea',
        'ru' => 'europe',
        'tr1' => 'europe',
        'jp1' => 'asia',
        'kr' => 'asia',
    ];

    private array $accountRegionMapping = [
        'euw1' => 'europe',
        'eun1' => 'europe',
        'na1' => 'americas',
        'br1' => 'americas',
        'la1' => 'americas',
        'la2' => 'americas',
        'oc1' => 'sea',
        'ru' => 'europe',
        'tr1' => 'europe',
        'jp1' => 'asia',
        'kr' => 'asia',
    ];

    public function __construct(HttpClientInterface $httpClient, string $riotApiKey, string $riotApiRegion)
    {
        $this->httpClient = $httpClient;
        $this->apiKey = $riotApiKey;
        $this->region = $this->resolveRegion($riotApiRegion);
    }

    private function resolveRegion(string $region): string
    {
        $region = strtolower($region);
        return $this->regionMapping[$region] ?? $region;
    }

    private function getMatchRegion(string $region): string
    {
        return $this->matchRegionMapping[$region] ?? 'europe';
    }

    private function getAccountRegion(string $region): string
    {
        return $this->accountRegionMapping[$region] ?? 'europe';
    }

    private function makeRequest(string $url): ?array
    {
        try {
            $response = $this->httpClient->request('GET', $url, [
                'headers' => [
                    'X-Riot-Token' => $this->apiKey,
                ],
            ]);

            $statusCode = $response->getStatusCode();
            if ($statusCode !== 200) {
                error_log("Riot API Error: HTTP $statusCode for URL: $url");
                return ['error' => "HTTP $statusCode", 'status_code' => $statusCode];
            }

            return $response->toArray();
        } catch (\Exception $e) {
            error_log("Riot API Exception: " . $e->getMessage() . " for URL: $url");
            return ['error' => $e->getMessage(), 'status_code' => $e->getCode()];
        }
    }

    public function getAccountByRiotId(string $gameName, string $tagLine, ?string $region = null): ?array
    {
        $region = $region ?? $this->region;
        $accountRegion = $this->getAccountRegion($region);
        
        // Use rawurlencode for %20 encoding instead of +
        $encodedGameName = rawurlencode($gameName);
        $encodedTagLine = rawurlencode($tagLine);
        
        $url = "https://{$accountRegion}.api.riotgames.com/riot/account/v1/accounts/by-riot-id/{$encodedGameName}/{$encodedTagLine}";
        
        error_log("RiotAPI: Account URL: $url");
        
        return $this->makeRequest($url);
    }

    public function getSummonerByPuuid(string $puuid, ?string $region = null): ?array
    {
        $region = $region ?? $this->region;
        $url = str_replace(
            ['{region}', '{puuid}'],
            [$region, urlencode($puuid)],
            self::RIOT_API_SUMMONER_URL
        );

        return $this->makeRequest($url);
    }

    public function getRankedEntries(string $summonerId, ?string $region = null): ?array
    {
        $region = $region ?? $this->region;
        $url = str_replace(
            ['{region}', '{summonerId}'],
            [$region, urlencode($summonerId)],
            self::RIOT_API_RANKED_URL
        );

        return $this->makeRequest($url);
    }

    public function getMatchIds(string $puuid, int $count = 20, ?string $region = null): ?array
    {
        $region = $region ?? $this->region;
        $matchRegion = $this->getMatchRegion($region);
        $url = str_replace(
            ['{region}', '{puuid}'],
            [$matchRegion, urlencode($puuid)],
            self::RIOT_API_MATCHES_URL
        );

        $url .= '?count=' . $count;

        return $this->makeRequest($url);
    }

    public function getMatch(string $matchId, ?string $region = null): ?array
    {
        $region = $region ?? $this->region;
        $matchRegion = $this->getMatchRegion($region);
        $url = str_replace(
            ['{region}', '{matchId}'],
            [$matchRegion, urlencode($matchId)],
            self::RIOT_API_MATCH_URL
        );

        return $this->makeRequest($url);
    }

    public function calculateStatsFromMatches(string $puuid, int $matchCount = 20, ?string $region = null): ?array
    {
        $matchIds = $this->getMatchIds($puuid, $matchCount, $region);

        if (!$matchIds) {
            return null;
        }

        $totalKills = 0;
        $totalDeaths = 0;
        $totalAssists = 0;
        $wins = 0;
        $losses = 0;
        $processedMatches = 0;

        foreach ($matchIds as $matchId) {
            $match = $this->getMatch($matchId, $region);

            if (!$match) {
                continue;
            }

            $participant = null;
            foreach ($match['info']['participants'] ?? [] as $p) {
                if ($p['puuid'] === $puuid) {
                    $participant = $p;
                    break;
                }
            }

            if (!$participant) {
                continue;
            }

            $totalKills += $participant['kills'] ?? 0;
            $totalDeaths += $participant['deaths'] ?? 0;
            $totalAssists += $participant['assists'] ?? 0;

            if ($participant['win'] ?? false) {
                $wins++;
            } else {
                $losses++;
            }

            $processedMatches++;
        }

        if ($processedMatches === 0) {
            return null;
        }

        $winRate = $processedMatches > 0 ? round(($wins / $processedMatches) * 100, 2) : 0;
        $kda = $totalDeaths > 0
            ? round(($totalKills + $totalAssists) / $totalDeaths, 2)
            : ($totalKills + $totalAssists);

        return [
            'matchesPlayed' => $processedMatches,
            'wins' => $wins,
            'losses' => $losses,
            'kills' => $totalKills,
            'deaths' => $totalDeaths,
            'assists' => $totalAssists,
            'winRate' => $winRate,
            'kda' => $kda,
        ];
    }

    public function getSoloQueueRank(array $rankedEntries): ?array
    {
        foreach ($rankedEntries as $entry) {
            if ($entry['queueType'] === 'RANKED_SOLO_5x5') {
                return [
                    'tier' => $entry['tier'],
                    'rank' => $entry['rank'],
                    'leaguePoints' => $entry['leaguePoints'],
                    'wins' => $entry['wins'],
                    'losses' => $entry['losses'],
                ];
            }
        }

        return null;
    }

    public function getRecentMatchDetails(string $puuid, int $count = 2, ?string $region = null): ?array
    {
        $matchIds = $this->getMatchIds($puuid, $count, $region);

        if (empty($matchIds)) {
            return null;
        }

        $recentMatches = [];

        foreach (array_slice($matchIds, 0, $count) as $matchId) {
            $match = $this->getMatch($matchId, $region);

            if (!$match || !isset($match['info'])) {
                continue;
            }

            $participant = null;
            foreach ($match['info']['participants'] ?? [] as $p) {
                if ($p['puuid'] === $puuid) {
                    $participant = $p;
                    break;
                }
            }

            if (!$participant) {
                continue;
            }

            $recentMatches[] = [
                'matchId' => $matchId,
                'win' => $participant['win'] ?? false,
                'kills' => $participant['kills'] ?? 0,
                'deaths' => $participant['deaths'] ?? 0,
                'assists' => $participant['assists'] ?? 0,
                'championName' => $participant['championName'] ?? 'Unknown',
                'gameMode' => $match['info']['gameMode'] ?? 'CLASSIC',
                'queueType' => $match['info']['queueId'] ?? 420,
                'gameCreation' => $match['info']['gameCreation'] ?? null,
                'gameDuration' => $match['info']['gameDuration'] ?? 0,
            ];
        }

        return $recentMatches;
    }
}
