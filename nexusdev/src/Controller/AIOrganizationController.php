<?php

namespace App\Controller;

use App\Entity\Organization;
use App\Entity\Team;
use App\Entity\TeamInvitation;
use App\Entity\Notification;
use App\Entity\Player;
use App\Entity\Game;
use App\Form\OrganizationType;
use App\Form\TeamType;
use App\Repository\OrganizationRepository;
use App\Repository\PlayerRepository;
use App\Repository\TeamRepository;
use App\Repository\TeamInvitationRepository;
use App\Repository\GameRepository;
use App\Repository\ProductRepository;
use App\Repository\StatisticRepository;
use App\Service\AIService;
use App\Service\AITeamCreatorService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/ai-organization')]
class AIOrganizationController extends AbstractController
{
    #[Route('/', name: 'app_ai_organization_back', methods: ['GET', 'POST'])]
    public function back(
        Request $request,
        OrganizationRepository $organizationRepository,
        PlayerRepository $playerRepository,
        TeamRepository $teamRepository,
        GameRepository $gameRepository,
        ProductRepository $productRepository,
        StatisticRepository $statisticRepository,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger,
        PaginatorInterface $paginator
    ): Response {
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('You must be logged in to manage an organization.');
        }

        $view = (string) $request->query->get('view', 'dashboard');
        if (!\in_array($view, ['dashboard', 'profile', 'teams', 'players', 'analytics', 'leaderboards', 'player-analytics', 'shop', 'notifications'], true)) {
            $view = 'dashboard';
        }

        // Initialize organization and forms
        $organization = $organizationRepository->findOneBy(['owner' => $user]) ?? new Organization();
        if (!$organization->getId()) {
            $organization->setOwner($user);
            $organization->setCreatedAt(new \DateTime());
        }

        $orgForm = $this->createForm(OrganizationType::class, $organization);
        $orgForm->handleRequest($request);

        if ($orgForm->isSubmitted() && $orgForm->isValid()) {
            /** @var UploadedFile $logoFile */
            $logoFile = $orgForm->get('logo')->getData();
            if ($logoFile) {
                $newFilename = uniqid() . '.' . $logoFile->guessExtension();
                try {
                    $logoFile->move(
                        $this->getParameter('logos_directory'),
                        $newFilename
                    );
                    $organization->setLogo($newFilename);
                } catch (FileException $e) {
                    // Handle exception if something happens during file upload
                }
            }

            $entityManager->persist($organization);
            $entityManager->flush();
            $this->addFlash('success', 'Organization updated successfully!');
        }

        $teamForm = $this->createForm(TeamType::class);
        $teamForm->handleRequest($request);

        // Get data for views
        $organizationTeams = $teamRepository->findBy(['organization' => $organization], ['name' => 'ASC']);
        $allTeams = $teamRepository->findAll();
        $players = $playerRepository->findBy([], ['score' => 'DESC']);
        $games = $gameRepository->findAll();

        // AI Features - Always available now
        $aiFeatures = [
            'available' => true, // Always true now
            'suggestedTeamNames' => ['Elite Squad', 'Vanguard Force', 'Apex Legion', 'Nova Division'],
            'socialMediaPost' => '🚀 Big news from our organization! Exciting things coming soon. #esports #gaming',
            'organizationInsights' => 'Professional esports organization dedicated to excellence in competitive gaming.'
        ];

        return $this->render('organization/back.html.twig', [
            'organization' => $organization,
            'orgForm' => $orgForm,
            'teamForm' => $teamForm,
            'hasOrganization' => $organization->getId() !== null,
            'organizationTeams' => $organizationTeams,
            'allTeams' => $allTeams,
            'players' => $players,
            'view' => $view,
            'games' => $games,
            'selectedGame' => null,
            'topPlayers' => array_slice($players, 0, 10),
            'recentMatches' => [],
            'leaderboardRankings' => [],
            'viewPlayer' => null,
            'playerStat' => null,
            'playerGame' => null,
            'playerRecentMatches' => [],
            'products' => [],
            'aiFeatures' => $aiFeatures,
        ]);
    }

    #[Route('/ai/create-team', name: 'app_ai_organization_create_team', methods: ['POST'])]
    public function createAITeam(
        Request $request,
        OrganizationRepository $organizationRepository,
        GameRepository $gameRepository,
        PlayerRepository $playerRepository,
        TeamRepository $teamRepository,
        TeamInvitationRepository $invitationRepository,
        EntityManagerInterface $entityManager
    ): Response {
        // Set JSON response header
        header('Content-Type: application/json');
        
        $user = $this->getUser();
        if (!$user) {
            return new Response(json_encode(['error' => 'Unauthorized']), 401);
        }

        $organization = $organizationRepository->findOneBy(['owner' => $user]);
        if (!$organization) {
            return new Response(json_encode(['error' => 'Organization not found']), 404);
        }

        $gameId = $request->request->getInt('gameId');
        $game = $gameRepository->find($gameId);
        if (!$game) {
            return new Response(json_encode(['error' => 'Game not found']), 404);
        }

        try {
            // Create team with template data
            $team = new Team();
            $teamNames = ['Elite Squad', 'Vanguard Force', 'Apex Legion', 'Nova Division', 'Phoenix Unit'];
            $team->setName($teamNames[array_rand($teamNames)]);
            $team->setDescription('Professional ' . $game->getName() . ' team focused on strategic gameplay and teamwork.');
            $team->setOrganization($organization);
            $team->setCreatedAt(new \DateTime());
            
            $entityManager->persist($team);
            $entityManager->flush();

            // Find and recruit players
            $availablePlayers = $playerRepository->findBy(['game' => $game, 'team' => null], ['score' => 'DESC'], 5);
            $recruitedCount = 0;

            foreach ($availablePlayers as $player) {
                if ($recruitedCount >= 5) break;

                // Create team invitation
                $invitation = new TeamInvitation();
                $invitation->setPlayer($player);
                $invitation->setTeam($team);
                $invitation->setStatus(TeamInvitation::STATUS_PENDING);
                $invitation->setMessage('Join our team! Your skills are perfect for our strategy.');
                $invitation->setCreatedAt(new \DateTime());

                $entityManager->persist($invitation);

                // Create notification for player
                if ($player->getUser()) {
                    $notification = new Notification();
                    $notification->setUser($player->getUser());
                    $notification->setMessage('🎮 Team "' . $team->getName() . '" wants you to join! Click here to respond.');
                    $entityManager->persist($notification);
                }

                $recruitedCount++;
            }

            $entityManager->flush();
            
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
                'ai_available' => true
            ];
            
            return new Response(json_encode($response), 200);
        } catch (\Exception $e) {
            return new Response(json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]), 500);
        }
    }

    #[Route('/ai/get-games', name: 'app_ai_organization_get_games', methods: ['GET'])]
    public function getGamesForAITeam(
        OrganizationRepository $organizationRepository,
        GameRepository $gameRepository,
        PlayerRepository $playerRepository
    ): Response {
        // Set JSON response header
        header('Content-Type: application/json');
        
        $user = $this->getUser();
        if (!$user) {
            return new Response(json_encode(['error' => 'Unauthorized']), 401);
        }

        $organization = $organizationRepository->findOneBy(['owner' => $user]);
        if (!$organization) {
            return new Response(json_encode(['error' => 'Organization not found']), 404);
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
        
        return new Response(json_encode($response), 200);
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
