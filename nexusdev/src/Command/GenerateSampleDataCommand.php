<?php

namespace App\Command;

use App\Entity\Achievement;
use App\Entity\GameMatch;
use App\Entity\MatchPlayer;
use App\Entity\Player;
use App\Entity\RankHistory;
use App\Entity\Team;
use App\Entity\Game;
use App\Service\EloRatingService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:generate-sample-data',
    description: 'Generate sample leaderboard and match data for testing',
)]
class GenerateSampleDataCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private EloRatingService $eloRatingService
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Generating Sample Leaderboard Data');

        // Get existing games
        $games = $this->entityManager->getRepository(Game::class)->findAll();
        if (empty($games)) {
            $io->error('No games found. Please create a game first.');
            return Command::FAILURE;
        }

        $game = $games[0];
        $io->info("Using game: {$game->getName()}");

        // Get or create teams
        $teams = $this->entityManager->getRepository(Team::class)->findBy(['game' => $game]);
        if (count($teams) < 2) {
            $io->warning('Not enough teams found. Creating sample teams...');
            $teams = $this->createSampleTeams($game, $io);
        }

        // Get existing players for the game
        $players = $this->entityManager->getRepository(Player::class)->findBy(['game' => $game]);
        if (count($players) < 2) {
            $io->warning('Not enough players found for this game. Please create players first.');
            return Command::FAILURE;
        }
        
        $io->info("Found " . count($players) . " players for {$game->getName()}");

        // Generate rank history
        $io->section('Generating Rank History...');
        $this->generateRankHistory($players, $game, $io);

        // Generate sample matches
        $io->section('Generating Sample Matches...');
        $this->generateSampleMatches($players, $teams, $game, $io);

        // Generate achievements
        $io->section('Generating Achievements...');
        $this->generateAchievements($game, $io);

        $io->success('Sample data generated successfully!');

        return Command::SUCCESS;
    }

    private function createSampleTeams(Game $game, SymfonyStyle $io): array
    {
        $teamNames = ['Cloud9', 'TSM', 'Team Liquid', '100 Thieves', 'Evil Geniuses', 'Counter Logic Gaming'];
        $teams = [];

        foreach (array_slice($teamNames, 0, 4) as $name) {
            $team = new Team();
            $team->setName($name);
            $team->setGame($game);
            $team->setCountry('USA');
            $this->entityManager->persist($team);
            $teams[] = $team;
            $io->text("Created team: $name");
        }

        $this->entityManager->flush();
        return $teams;
    }

    private function createSamplePlayers(Game $game, array $teams, SymfonyStyle $io): array
    {
        $playerData = [
            ['Faker', 'Mid', 'KR'],
            ['Chovy', 'Mid', 'KR'],
            ['ShowMaker', 'Mid', 'KR'],
            ['Deft', 'ADC', 'KR'],
            ['Gumayusi', 'ADC', 'KR'],
            ['Keria', 'Support', 'KR'],
            ['Zeus', 'Top', 'KR'],
            ['Oner', 'Jungle', 'KR'],
            ['Caps', 'Mid', 'EU'],
            ['Perkz', 'Mid', 'EU'],
            ['Rekkles', 'ADC', 'EU'],
            ['Jankos', 'Jungle', 'EU'],
            ['Doublelift', 'ADC', 'NA'],
            ['Bjergsen', 'Mid', 'NA'],
            ['Impact', 'Top', 'KR'],
        ];

        $players = [];
        $teamIndex = 0;

        foreach ($playerData as $index => $data) {
            $player = new Player();
            $player->setNickname($data[0]);
            $player->setRole($data[1]);
            $player->setNationality($data[2]);
            $player->setGame($game);
            $player->setScore(rand(1000, 2500));
            $player->setIsPro(true);
            
            // Assign to teams (3-4 players per team)
            if ($index % 4 === 0 && $index > 0) {
                $teamIndex = ($teamIndex + 1) % count($teams);
            }
            if (isset($teams[$teamIndex])) {
                $player->setTeam($teams[$teamIndex]);
            }
            
            $this->entityManager->persist($player);
            $players[] = $player;
            $io->text("Created player: {$data[0]} ({$data[1]})");
        }

        $this->entityManager->flush();
        return $players;
    }

    private function generateRankHistory(array $players, Game $game, SymfonyStyle $io): void
    {
        $season = $this->eloRatingService->getCurrentSeason();
        $regions = ['NA', 'EU', 'KR', 'CN', 'BR'];
        
        // Sort players by score for initial ranking
        usort($players, fn($a, $b) => $b->getScore() <=> $a->getScore());

        foreach ($players as $index => $player) {
            // Starting ELO based on score
            $baseElo = min(2800, 1200 + ($player->getScore() / 2));
            $elo = rand($baseElo - 200, $baseElo + 200);
            
            $rankHistory = new RankHistory();
            $rankHistory->setPlayer($player);
            $rankHistory->setGame($game);
            $rankHistory->setRank($index + 1);
            $rankHistory->setEloRating($elo);
            $rankHistory->setRegion($regions[array_rand($regions)]);
            $rankHistory->setSeason($season);
            $rankHistory->setRecordedAt(new \DateTimeImmutable());
            
            $this->entityManager->persist($rankHistory);
            
            // Add some historical entries
            for ($i = 1; $i <= 5; $i++) {
                $pastElo = max(800, $elo - rand(-100, 200));
                $pastRank = max(1, $index + rand(-5, 5));
                
                $pastHistory = new RankHistory();
                $pastHistory->setPlayer($player);
                $pastHistory->setGame($game);
                $pastHistory->setRank($pastRank);
                $pastHistory->setEloRating($pastElo);
                $pastHistory->setRegion($regions[array_rand($regions)]);
                $pastHistory->setSeason($season);
                $pastHistory->setRecordedAt(new \DateTimeImmutable("-$i days"));
                
                $this->entityManager->persist($pastHistory);
            }
        }

        $this->entityManager->flush();
        $io->text('Generated rank history for ' . count($players) . ' players');
    }

    private function generateSampleMatches(array $players, array $teams, Game $game, SymfonyStyle $io): void
    {
        $maps = ['Summoner\'s Rift', 'Howling Abyss', 'Twisted Treeline'];
        
        for ($i = 0; $i < 20; $i++) {
            $teamA = $teams[array_rand($teams)];
            $teamB = $teams[array_rand($teams)];
            
            while ($teamB === $teamA) {
                $teamB = $teams[array_rand($teams)];
            }

            $match = new GameMatch();
            $match->setGame($game);
            $match->setTeamA($teamA);
            $match->setTeamB($teamB);
            $match->setTeamAName($teamA->getName());
            $match->setTeamBName($teamB->getName());
            $match->setMap($maps[array_rand($maps)]);
            $match->setMatchDate(new \DateTimeImmutable("-$i days"));
            $match->setStatus('completed');
            
            // Random scores
            $scoreA = rand(0, 2);
            $scoreB = rand(0, 2);
            $match->setTeamAScore($scoreA);
            $match->setTeamBScore($scoreB);
            
            $this->entityManager->persist($match);
            $this->entityManager->flush();

            // Add match players
            $teamAPlayers = array_filter($players, fn($p) => $p->getTeam() === $teamA);
            $teamBPlayers = array_filter($players, fn($p) => $p->getTeam() === $teamB);

            foreach ($teamAPlayers as $player) {
                $this->createMatchPlayer($match, $player, $teamA, $scoreA > $scoreB);
            }

            foreach ($teamBPlayers as $player) {
                $this->createMatchPlayer($match, $player, $teamB, $scoreB > $scoreA);
            }

            $io->text("Created match: {$teamA->getName()} vs {$teamB->getName()}");
        }

        $this->entityManager->flush();
    }

    private function createMatchPlayer(GameMatch $match, Player $player, Team $team, bool $isWinner): void
    {
        $matchPlayer = new MatchPlayer();
        $matchPlayer->setGameMatch($match);
        $matchPlayer->setPlayer($player);
        $matchPlayer->setTeam($team);
        $matchPlayer->setIsWinner($isWinner);
        $matchPlayer->setKills(rand(0, 15));
        $matchPlayer->setDeaths(rand(0, 10));
        $matchPlayer->setAssists(rand(0, 20));
        $matchPlayer->setEloChange($isWinner ? rand(10, 30) : rand(-30, -10));
        $matchPlayer->setPositionX(rand(0, 100) . '.' . rand(0, 99));
        $matchPlayer->setPositionY(rand(0, 100) . '.' . rand(0, 99));
        
        $this->entityManager->persist($matchPlayer);
    }

    private function generateAchievements(Game $game, SymfonyStyle $io): void
    {
        $achievements = [
            ['First Blood', 'Get your first kill', 'kills', 'common', 10, 1],
            ['Killing Spree', 'Get 10 kills in a match', 'kills', 'common', 25, 10],
            ['Dominating', 'Win 10 matches', 'wins', 'rare', 50, 10],
            ['Unstoppable', 'Win 50 matches', 'wins', 'epic', 100, 50],
            ['Legendary', 'Win 100 matches', 'wins', 'legendary', 250, 100],
            ['Veteran', 'Play 100 matches', 'matches_played', 'rare', 75, 100],
            ['Elite Player', 'Reach 1500 ELO', 'score', 'epic', 150, 1500],
            ['PRO Status', 'Become a PRO player', 'pro_status', 'legendary', 500, 1],
        ];

        foreach ($achievements as $data) {
            $achievement = new Achievement();
            $achievement->setName($data[0]);
            $achievement->setDescription($data[1]);
            $achievement->setType($data[2]);
            $achievement->setRarity($data[3]);
            $achievement->setPoints($data[4]);
            $achievement->setRequiredValue($data[5]);
            $achievement->setGame($game);
            $achievement->setIcon('trophy');
            
            $this->entityManager->persist($achievement);
            $io->text("Created achievement: {$data[0]}");
        }

        $this->entityManager->flush();
    }
}
