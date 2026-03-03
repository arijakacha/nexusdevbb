<?php

namespace App\Service;

use App\Entity\Player;
use App\Entity\RankHistory;
use App\Entity\Game;
use Doctrine\ORM\EntityManagerInterface;

class EloRatingService
{
    private const K_FACTOR = 32; // Standard K-factor
    private const STARTING_RATING = 1200;

    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    /**
     * Calculate expected score for player A against player B
     */
    public function calculateExpectedScore(int $ratingA, int $ratingB): float
    {
        return 1 / (1 + pow(10, ($ratingB - $ratingA) / 400));
    }

    /**
     * Calculate new rating after a match
     * 
     * @param int $currentRating Current ELO rating
     * @param float $expectedScore Expected score (0 to 1)
     * @param float $actualScore Actual score (1 for win, 0 for loss, 0.5 for draw)
     * @return int New ELO rating
     */
    public function calculateNewRating(int $currentRating, float $expectedScore, float $actualScore): int
    {
        $ratingChange = self::K_FACTOR * ($actualScore - $expectedScore);
        return (int) round($currentRating + $ratingChange);
    }

    /**
     * Update player ratings after a match
     * 
     * @param Player $winner The winning player
     * @param Player $loser The losing player
     * @param Game $game The game being played
     * @param string|null $region Optional region for regional rankings
     * @return array Array with 'winnerChange' and 'loserChange'
     */
    public function updateRatings(Player $winner, Player $loser, Game $game, ?string $region = null): array
    {
        // Get current ratings
        $winnerRating = $this->getCurrentRating($winner, $game);
        $loserRating = $this->getCurrentRating($loser, $game);

        // Calculate expected scores
        $winnerExpected = $this->calculateExpectedScore($winnerRating, $loserRating);
        $loserExpected = $this->calculateExpectedScore($loserRating, $winnerRating);

        // Calculate new ratings (1 for win, 0 for loss)
        $newWinnerRating = $this->calculateNewRating($winnerRating, $winnerExpected, 1.0);
        $newLoserRating = $this->calculateNewRating($loserRating, $loserExpected, 0.0);

        // Record rating changes
        $winnerChange = $newWinnerRating - $winnerRating;
        $loserChange = $newLoserRating - $loserRating;

        // Create rank history entries
        $this->recordRatingChange($winner, $game, $newWinnerRating, $region);
        $this->recordRatingChange($loser, $game, $newLoserRating, $region);

        return [
            'winnerChange' => $winnerChange,
            'loserChange' => $loserChange,
            'winnerNewRating' => $newWinnerRating,
            'loserNewRating' => $newLoserRating
        ];
    }

    /**
     * Get current rating for a player in a specific game
     */
    private function getCurrentRating(Player $player, Game $game): int
    {
        $rankHistoryRepo = $this->entityManager->getRepository(RankHistory::class);
        $latest = $rankHistoryRepo->findLatestPlayerRank($player->getId(), $game->getId());
        
        return $latest ? $latest->getEloRating() : self::STARTING_RATING;
    }

    /**
     * Record a rating change in the history
     */
    private function recordRatingChange(Player $player, Game $game, int $newRating, ?string $region = null): void
    {
        // Get current season
        $season = $this->getCurrentSeason();

        // Get current global rank
        $rankHistoryRepo = $this->entityManager->getRepository(RankHistory::class);
        $globalRankings = $rankHistoryRepo->findGlobalRankings($game->getId(), $season);
        
        // Calculate rank based on ELO rating
        $rank = 1;
        foreach ($globalRankings as $entry) {
            if ($entry->getEloRating() > $newRating) {
                $rank++;
            }
        }

        // Create new rank history entry
        $rankHistory = new RankHistory();
        $rankHistory->setPlayer($player);
        $rankHistory->setGame($game);
        $rankHistory->setEloRating($newRating);
        $rankHistory->setRank($rank);
        $rankHistory->setRegion($region);
        $rankHistory->setSeason($season);

        $this->entityManager->persist($rankHistory);
        $this->entityManager->flush();
    }

    /**
     * Get current season string (e.g., "2024-Spring", "2024-Summer")
     */
    public function getCurrentSeason(): string
    {
        $now = new \DateTimeImmutable();
        $year = $now->format('Y');
        $month = (int) $now->format('n');
        
        // Define seasons
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

    /**
     * Get tier based on ELO rating
     */
    public static function getTier(int $rating): string
    {
        return match (true) {
            $rating >= 2400 => 'Challenger',
            $rating >= 2200 => 'Grandmaster',
            $rating >= 2000 => 'Master',
            $rating >= 1800 => 'Diamond',
            $rating >= 1600 => 'Platinum',
            $rating >= 1400 => 'Gold',
            $rating >= 1200 => 'Silver',
            default => 'Bronze'
        };
    }

    /**
     * Get tier color for UI display
     */
    public static function getTierColor(string $tier): string
    {
        return match ($tier) {
            'Challenger' => '#ff4655',
            'Grandmaster' => '#ff4655',
            'Master' => '#9b59b6',
            'Diamond' => '#3498db',
            'Platinum' => '#1abc9c',
            'Gold' => '#f1c40f',
            'Silver' => '#95a5a6',
            'Bronze' => '#cd7f32',
            default => '#95a5a6'
        };
    }
}
