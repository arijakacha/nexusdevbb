<?php

namespace App\Command;

use App\Entity\Player;
use App\Service\RiotStatsSyncService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:riot:sync-stats',
    description: 'Sync League of Legends stats from Riot Games API',
)]
class SyncRiotStatsCommand extends Command
{
    private RiotStatsSyncService $riotStatsSyncService;
    private EntityManagerInterface $entityManager;

    public function __construct(
        RiotStatsSyncService $riotStatsSyncService,
        EntityManagerInterface $entityManager
    ) {
        parent::__construct();
        $this->riotStatsSyncService = $riotStatsSyncService;
        $this->entityManager = $entityManager;
    }

    protected function configure(): void
    {
        $this
            ->addArgument('player', InputArgument::OPTIONAL, 'Player ID to sync (omit to sync all players)')
            ->addOption('region', 'r', InputOption::VALUE_REQUIRED, 'Override region (euw1, na1, kr, etc.)')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force sync even if recently synced');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $playerId = $input->getArgument('player');
        $region = $input->getOption('region');

        if ($playerId) {
            // Sync single player
            $player = $this->entityManager->getRepository(Player::class)->find($playerId);

            if (!$player) {
                $io->error("Player with ID {$playerId} not found.");
                return Command::FAILURE;
            }

            $user = $player->getUser();
            if (!$user || !$user->getRiotSummonerName()) {
                $io->error("Player has no Riot summoner name configured.");
                return Command::FAILURE;
            }

            $io->info("Syncing stats for player: {$player->getNickname()} (Summoner: {$user->getRiotSummonerName()})");

            $success = $this->riotStatsSyncService->syncPlayerStats($player, $region);

            if ($success) {
                $io->success("Stats synced successfully!");
                return Command::SUCCESS;
            } else {
                $io->error("Failed to sync stats. Check if the summoner name is correct and the Riot API is accessible.");
                return Command::FAILURE;
            }
        } else {
            // Sync all players
            $io->info("Syncing stats for all players with Riot accounts...");

            $results = $this->riotStatsSyncService->syncAllPlayers();

            $io->success("Sync completed!");
            $io->table(
                ['Status', 'Count'],
                [
                    ['Success', $results['success']],
                    ['Failed', $results['failed']],
                    ['Skipped (no Riot account)', $results['skipped']],
                ]
            );

            return Command::SUCCESS;
        }
    }
}
