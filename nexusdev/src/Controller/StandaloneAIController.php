<?php

namespace App\Controller;

use App\Entity\Team;
use App\Entity\TeamInvitation;
use App\Entity\Notification;
use App\Entity\Player;
use App\Entity\Game;
use App\Repository\OrganizationRepository;
use App\Repository\PlayerRepository;
use App\Repository\TeamRepository;
use App\Repository\TeamInvitationRepository;
use App\Repository\GameRepository;
use App\Service\AIService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/standalone-ai')]
class StandaloneAIController extends AbstractController
{
    #[Route('/get-games', name: 'app_standalone_ai_get_games', methods: ['GET'])]
    public function getGamesForAITeam(
        OrganizationRepository $organizationRepository,
        GameRepository $gameRepository,
        PlayerRepository $playerRepository
    ): Response {
        // Temporarily bypass authentication for testing
        $user = $this->getUser();
        if (!$user) {
            // Return mock data for testing
            $response = [
                'success' => true,
                'games' => [
                    [
                        'id' => 1,
                        'name' => 'League of Legends',
                        'playerCount' => 5
                    ]
                ],
                'ai_available' => true,
                'debug' => 'Using mock data - user not logged in'
            ];
            return new Response(json_encode($response), 200, ['Content-Type' => 'application/json']);
        }

        $organization = $organizationRepository->findOneBy(['owner' => $user]);
        if (!$organization) {
            return new Response(json_encode(['error' => 'Organization not found']), 404, ['Content-Type' => 'application/json']);
        }

        // Get games with available players
        $games = $gameRepository->findAll();
        $gamesData = [];
        
        foreach ($games as $game) {
            $availablePlayers = $playerRepository->findBy(['game' => $game, 'team' => null]);
            if (count($availablePlayers) > 0) {
                $gamesData[] = [
                    'id' => $game->getId(),
                    'name' => $game->getName(),
                    'playerCount' => count($availablePlayers)
                ];
            }
        }
        
        $response = [
            'success' => true,
            'games' => $gamesData,
            'ai_available' => true
        ];
        
        return new Response(json_encode($response), 200, ['Content-Type' => 'application/json']);
    }

    #[Route('/create-team', name: 'app_standalone_ai_create_team', methods: ['POST'])]
    public function createAITeam(
        Request $request,
        OrganizationRepository $organizationRepository,
        GameRepository $gameRepository,
        PlayerRepository $playerRepository,
        TeamRepository $teamRepository,
        TeamInvitationRepository $invitationRepository,
        EntityManagerInterface $entityManager,
        AIService $aiService
    ): Response {
        // Check if user is logged in
        $user = $this->getUser();
        
        if (!$user) {
            // PURE MOCK PATH - NO DATABASE OPERATIONS WHATSOEVER
            error_log('AI Team Creation: Using mock data path - user not logged in');
            
            $teamNames = ['Elite Squad', 'Vanguard Force', 'Apex Legion', 'Nova Division', 'Phoenix Unit'];
            $suffixes = ['Pro', 'Elite', 'Prime', 'Ultimate', 'Masters', 'Legends', 'Champions', 'Titans'];
            $randomName = $teamNames[array_rand($teamNames)];
            $randomSuffix = $suffixes[array_rand($suffixes)];
            $uniqueTeamName = $randomName . ' ' . $randomSuffix . ' ' . rand(100, 999);
            
            $response = [
                'success' => true,
                'team' => [
                    'id' => 999,
                    'name' => $uniqueTeamName,
                    'description' => 'Professional League of Legends team focused on strategic gameplay and teamwork, built for competitive success.'
                ],
                'details' => [
                    'name' => $uniqueTeamName,
                    'description' => 'Professional League of Legends team focused on strategic gameplay and teamwork, built for competitive success.',
                    'strategy' => 'Aggressive early-game dominance with coordinated team fights',
                    'roles' => ['Top Lane', 'Jungle', 'Mid Lane', 'ADC', 'Support']
                ],
                'recruitedPlayers' => 5,
                'ai_available' => true,
                'debug' => 'Using pure mock data - no database operations or entity creation'
            ];
            
            error_log('AI Team Creation: Returning mock response');
            return new Response(json_encode($response), 200, ['Content-Type' => 'application/json']);
        }

        error_log('AI Team Creation: User is logged in, proceeding with database operations');
        
        // REAL DATABASE OPERATIONS - ONLY FOR LOGGED IN USERS
        $organization = $organizationRepository->findOneBy(['owner' => $user]);
        if (!$organization) {
            return new Response(json_encode(['error' => 'Organization not found']), 404, ['Content-Type' => 'application/json']);
        }

        $gameId = $request->request->getInt('gameId');
        $game = $gameRepository->find($gameId);
        if (!$game) {
            return new Response(json_encode(['error' => 'Game not found']), 404, ['Content-Type' => 'application/json']);
        }

        try {
            // Create team with template data
            $team = new Team();
            $teamNames = ['Elite Squad', 'Vanguard Force', 'Apex Legion', 'Nova Division', 'Phoenix Unit'];
            $suffixes = ['Pro', 'Elite', 'Prime', 'Ultimate', 'Masters', 'Legends', 'Champions', 'Titans'];
            $randomName = $teamNames[array_rand($teamNames)];
            $randomSuffix = $suffixes[array_rand($suffixes)];
            $uniqueTeamName = $randomName . ' ' . $randomSuffix . ' ' . rand(100, 999);
            
            $team->setName($uniqueTeamName);
            
            // Generate AI-powered description
            try {
                $aiDescription = $aiService->generateTeamDescription($game->getName(), $uniqueTeamName);
                $team->setDescription($aiDescription);
                error_log('AI Team Creation: Generated AI description: ' . $aiDescription);
            } catch (\Exception $aiError) {
                // Fallback to template description if AI fails
                $fallbackDescription = 'Professional ' . $game->getName() . ' team focused on strategic gameplay and teamwork. Built for competitive success with skilled players.';
                $team->setDescription($fallbackDescription);
                error_log('AI Team Creation: AI failed, using fallback description: ' . $aiError->getMessage());
            }
            
            $team->setOrganization($organization);
            $team->setGame($game); // IMPORTANT: Set the game!
            $team->setCreatedAt(new \DateTimeImmutable()); // Fixed: Use DateTimeImmutable
            
            $entityManager->persist($team);
            $entityManager->flush();

            // Find and recruit REAL free players (not on any team)
            $availablePlayers = $playerRepository->findBy(['game' => $game, 'team' => null], ['score' => 'DESC'], 5);
            $recruitedCount = 0;

            foreach ($availablePlayers as $player) {
                if ($recruitedCount >= 5) break;

                // Create team invitation for REAL player
                $invitation = new TeamInvitation();
                $invitation->setPlayer($player);
                $invitation->setTeam($team);
                $invitation->setStatus(TeamInvitation::STATUS_PENDING);
                $invitation->setMessage('🎮 Join ' . $team->getName() . '! Your skills are perfect for our ' . $game->getName() . ' team. We believe you\'ll be a valuable asset to our roster.');
                $invitation->setCreatedAt(new \DateTime()); // Fixed: Use DateTime for TeamInvitation

                $entityManager->persist($invitation);

                // Create notification for player if they have a user account
                if ($player->getUser()) {
                    $notification = new Notification();
                    $notification->setUser($player->getUser());
                    $notification->setMessage('🎮 Team "' . $team->getName() . '" wants you to join! Click here to respond to the invitation.');
                    $entityManager->persist($notification);
                    error_log('AI Team Creation: Sent notification to player: ' . $player->getNickname());
                }

                $recruitedCount++;
                error_log('AI Team Creation: Recruited player: ' . $player->getNickname() . ' (Score: ' . $player->getScore() . ')');
            }

            $entityManager->flush();
            
            error_log('AI Team Creation: Successfully created team and recruited ' . $recruitedCount . ' players');
            
            $response = [
                'success' => true,
                'team' => [
                    'id' => $team->getId(),
                    'name' => $team->getName(),
                    'description' => $team->getDescription()
                ],
                'details' => [
                    'name' => $team->getName(),
                    'description' => $team->getDescription(),
                    'strategy' => 'Aggressive early-game dominance with coordinated team fights',
                    'roles' => $this->getGameRoles($game->getName())
                ],
                'recruitedPlayers' => $recruitedCount,
                'ai_available' => true,
                'debug' => 'Created real team with AI description and recruited ' . $recruitedCount . ' free players'
            ];
            
            return new Response(json_encode($response), 200, ['Content-Type' => 'application/json']);
        } catch (\Exception $e) {
            error_log('AI Team Creation Error: ' . $e->getMessage());
            return new Response(json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]), 500, ['Content-Type' => 'application/json']);
        }
    }

    private function getGameRoles(string $gameName): array
    {
        $roles = [
            'League of Legends' => ['Top Lane', 'Jungle', 'Mid Lane', 'ADC', 'Support'],
            'Valorant' => ['Duelist', 'Controller', 'Initiator', 'Sentinel', 'Smokes'],
            'CS:GO' => ['Entry Fragger', 'AWPer', 'Support', 'Lurker', 'IGL'],
            'Overwatch' => ['Tank', 'Damage', 'Support', 'Flex', 'Shotcaller'],
            'Fortnite' => ['IGL', 'Builder', 'Shotcaller', 'Support', 'Aggressive'],
            'Rocket League' => ['Striker', 'Playmaker', 'Goalkeeper', 'Flex', 'Rotator'],
            'Apex Legends' => ['IGL', 'Duelist', 'Support', 'Scout', 'Flex']
        ];

        return $roles[$gameName] ?? ['Player 1', 'Player 2', 'Player 3', 'Player 4', 'Player 5'];
    }
}
