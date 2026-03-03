<?php

namespace App\Controller;

use App\Entity\Organization;
use App\Entity\Team;
use App\Entity\TeamInvitation;
use App\Entity\Notification;
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

#[Route('/BOrganization')]
final class OrganizationController extends AbstractController
{
    #[Route(name: 'app_organization_back', methods: ['GET', 'POST'])]
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
        PaginatorInterface $paginator,
        AIService $aiService
    ): Response {
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('You must be logged in to manage an organization.');
        }

        $view = (string) $request->query->get('view', 'dashboard');
        if (!\in_array($view, ['dashboard', 'profile', 'teams', 'players', 'analytics', 'leaderboards', 'player-analytics', 'shop', 'notifications'], true)) {
            $view = 'dashboard';
        }

        // Initialize sorting variables
        $sort = $request->query->get('sort', 'id');
        $direction = $request->query->get('direction', 'ASC');

        // Admin view: list all organizations (for any user having ROLE_ADMIN)
        if ($this->isGranted('ROLE_ADMIN')) {
            $qb = $organizationRepository->createQueryBuilder('o');

            $search = $request->query->get('search');
            if ($search) {
                $qb->andWhere('o.name LIKE :search')
                   ->setParameter('search', '%' . $search . '%');
            }

            // Sorting validation
            $allowedSorts = ['id', 'name', 'isValidated', 'createdAt'];
            $allowedDirections = ['ASC', 'DESC'];
            
            if (!in_array($sort, $allowedSorts)) {
                $sort = 'id';
            }
            if (!in_array(strtoupper($direction), $allowedDirections)) {
                $direction = 'ASC';
            }
            
            $qb->orderBy('o.' . $sort, $direction);

            // Get results manually and create pagination array
            $query = $qb->getQuery();
            $results = $query->getResult();
            
            // Use paginator with array to bypass OrderByWalker
            $pagination = $paginator->paginate(
                $results,
                $request->query->getInt('page', 1),
                10
            );

            // Get organization for editing (if id provided)
            $orgId = $request->query->getInt('id', 0);
            if ($orgId > 0) {
                $organization = $organizationRepository->find($orgId);
            } else {
                $organization = new Organization();
            }
            
            if (!$organization) {
                $organization = new Organization();
            }

            // Organization form handling
            $form = $this->createForm(OrganizationType::class, $organization);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                /** @var UploadedFile $logoFile */
                $logoFile = $form->get('logo')->getData();
                
                if ($logoFile) {
                    $originalFilename = pathinfo($logoFile->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = $slugger->slug($originalFilename);
                    $newFilename = $safeFilename.'-'.uniqid().'.'.$logoFile->guessExtension();
                    
                    try {
                        $logoFile->move(
                            $this->getParameter('logos_directory'),
                            $newFilename
                        );
                        $organization->setLogo($newFilename);
                    } catch (FileException $e) {
                        $this->addFlash('error', 'Error uploading logo file.');
                    }
                }
                
                $isNew = $organization->getId() === null;
                if ($isNew) {
                    // Set current user as owner for new organization
                    $organization->setOwner($user);
                    $entityManager->persist($organization);
                }
                $entityManager->flush();

                $this->addFlash('success', $isNew ? 'Organization created successfully.' : 'Organization updated successfully.');

                return $this->redirectToRoute('app_organization_back', [], Response::HTTP_SEE_OTHER);
            }

            return $this->render('organization/back_admin.html.twig', [
                'pagination' => $pagination,
                'sort' => $sort,
                'direction' => $direction,
                'form' => $form->createView(),
                'editing' => $organization->getId() !== null,
                'currentOrganization' => $organization,
            ]);
        }

        // Get or create organization for this user
        $organization = $organizationRepository->findOneBy(['owner' => $user]);
        if (!$organization) {
            $organization = new Organization();
            $organization->setOwner($user);
        }

        // Organization form handling
        $orgForm = $this->createForm(OrganizationType::class, $organization);
        $orgForm->handleRequest($request);

        if ($orgForm->isSubmitted() && $orgForm->isValid()) {
            /** @var UploadedFile $logoFile */
            $logoFile = $orgForm->get('logo')->getData();
            
            if ($logoFile) {
                $originalFilename = pathinfo($logoFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$logoFile->guessExtension();
                
                try {
                    $logoFile->move(
                        $this->getParameter('logos_directory'),
                        $newFilename
                    );
                    $organization->setLogo($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Error uploading logo file.');
                }
            }
            
            $isNew = $organization->getId() === null;
            if ($isNew) {
                $entityManager->persist($organization);
            }
            $entityManager->flush();

            $this->addFlash('success', $isNew ? 'Organization created successfully.' : 'Organization updated successfully.');

            $redirectView = $isNew ? 'profile' : $view;
            return $this->redirectToRoute('app_organization_back', ['view' => $redirectView], Response::HTTP_SEE_OTHER);
        }

        // Team creation form (only if organization exists)
        $team = new Team();
        $teamForm = null;
        $organizationTeams = [];
        
        if ($organization->getId()) {
            $teamForm = $this->createForm(TeamType::class, $team);
            $teamForm->handleRequest($request);

            if ($teamForm->isSubmitted() && $teamForm->isValid()) {
                /** @var UploadedFile $teamLogoFile */
                $teamLogoFile = $teamForm->get('logo')->getData();
                
                if ($teamLogoFile) {
                    $originalFilename = pathinfo($teamLogoFile->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = $slugger->slug($originalFilename);
                    $newFilename = $safeFilename.'-'.uniqid().'.'.$teamLogoFile->guessExtension();
                    
                    try {
                        $teamLogoFile->move(
                            $this->getParameter('logos_directory'),
                            $newFilename
                        );
                        $team->setLogo($newFilename);
                    } catch (FileException $e) {
                        $this->addFlash('error', 'Error uploading team logo file.');
                    }
                }
                
                $team->setOrganization($organization);
                $entityManager->persist($team);
                $entityManager->flush();

                $this->addFlash('success', 'Team created successfully.');

                return $this->redirectToRoute('app_organization_back', ['view' => 'profile'], Response::HTTP_SEE_OTHER);
            }
            
            // Get organization's teams
            $organizationTeams = $teamRepository->findBy(['organization' => $organization]);
        }

        $allTeams = [];
        if ($organization->getId() && $view === 'teams') {
            $allTeams = $teamRepository->findAll();
        }

        // Get all PRO players for scouting
        $players = [];
        if ($view === 'dashboard' || $view === 'players') {
            $players = $playerRepository->findProPlayers();
        }

        // Load analytics data for analytics view
        $games = [];
        $selectedGame = null;
        $topPlayers = [];
        $recentMatches = [];
        if ($view === 'analytics') {
            $games = $gameRepository->findAll();
            $selectedGameId = $request->query->getInt('game', $games[0]->getId() ?? 0);
            $selectedGame = $gameRepository->find($selectedGameId);
            if ($selectedGame) {
                $topPlayers = $statisticRepository->findTopPlayersByGame($selectedGameId, 10);
            }
        }

        // Load leaderboards data for leaderboards view
        $leaderboardRankings = [];
        if ($view === 'leaderboards') {
            $games = $gameRepository->findAll();
            $selectedGameId = $request->query->getInt('game', $games[0]->getId() ?? 0);
            $selectedGame = $gameRepository->find($selectedGameId);
            if ($selectedGame) {
                $statistics = $statisticRepository->findTopPlayersByGame($selectedGameId, 100);
                foreach ($statistics as $index => $stat) {
                    $player = $stat->getPlayer();
                    if (!$player) continue;
                    $score = $player->getScore();
                    $leaderboardRankings[] = [
                        'rank' => $index + 1,
                        'player' => $player,
                        'statistic' => $stat,
                        'eloRating' => $score,
                        'winRate' => $stat->getWinRate(),
                        'matches' => $stat->getMatchesPlayed(),
                        'tier' => $this->getTierFromScore($score)
                    ];
                }
            }
        }

        // Load player analytics data for player-analytics view
        $viewPlayer = null;
        $playerStat = null;
        $playerGame = null;
        $playerRecentMatches = [];
        if ($view === 'player-analytics') {
            $playerId = $request->query->getInt('playerId');
            if ($playerId) {
                $viewPlayer = $playerRepository->find($playerId);
                if ($viewPlayer) {
                    $playerGame = $viewPlayer->getGame();
                    $playerStat = $statisticRepository->findPlayerStats($playerId, $playerGame->getId());
                }
            }
        }

        // Load products for shop view
        $products = [];
        if ($view === 'shop') {
            $products = $productRepository->findBy(['deletedAt' => null], ['name' => 'ASC']);
        }

        // AI-powered features
        $aiFeatures = [
            'available' => $aiService->isAvailable(),
            'suggestedTeamNames' => [],
            'socialMediaPost' => '',
            'organizationInsights' => ''
        ];

        if ($organization->getId() && $aiService->isAvailable()) {
            $aiFeatures['suggestedTeamNames'] = $aiService->generateTeamNames($organization->getName());
            $aiFeatures['socialMediaPost'] = $aiService->generateSocialMediaPost($organization->getName());
            $aiFeatures['organizationInsights'] = $aiService->generateOrganizationDescription($organization->getName());
        }

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
            'selectedGame' => $selectedGame,
            'topPlayers' => $topPlayers,
            'recentMatches' => $recentMatches,
            'leaderboardRankings' => $leaderboardRankings,
            'viewPlayer' => $viewPlayer,
            'playerStat' => $playerStat,
            'playerGame' => $playerGame,
            'playerRecentMatches' => $playerRecentMatches,
            'products' => $products,
            'aiFeatures' => $aiFeatures,
        ]);
    }

    #[Route('/recruit/{playerId}/{teamId}', name: 'app_organization_recruit', methods: ['POST'])]
    public function recruit(
        int $playerId,
        int $teamId,
        OrganizationRepository $organizationRepository,
        PlayerRepository $playerRepository,
        TeamRepository $teamRepository,
        TeamInvitationRepository $invitationRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('You must be logged in.');
        }

        $organization = $organizationRepository->findOneBy(['owner' => $user]);
        if (!$organization) {
            $this->addFlash('error', 'You must create an organization first.');
            return $this->redirectToRoute('app_organization_back', ['view' => 'dashboard']);
        }

        $player = $playerRepository->find($playerId);
        $team = $teamRepository->find($teamId);

        if (!$player || !$team) {
            $this->addFlash('error', 'Player or team not found.');
            return $this->redirectToRoute('app_organization_back', ['view' => 'players']);
        }

        // Verify team belongs to this organization
        if ($team->getOrganization() !== $organization) {
            $this->addFlash('error', 'This team does not belong to your organization.');
            return $this->redirectToRoute('app_organization_back', ['view' => 'players']);
        }

        // Check if player already has a pending invitation from this team
        $existingInvitation = $invitationRepository->findExistingPendingInvitation($player, $team);
        if ($existingInvitation) {
            $this->addFlash('warning', 'You have already sent an invitation to this player for this team.');
            return $this->redirectToRoute('app_organization_back', ['view' => 'players']);
        }

        // Create invitation
        $invitation = new TeamInvitation();
        $invitation->setPlayer($player);
        $invitation->setTeam($team);
        $invitation->setStatus(TeamInvitation::STATUS_PENDING);
        $invitation->setMessage('You have been invited to join ' . $team->getName());
        $invitation->setCreatedAt(new \DateTime());

        $entityManager->persist($invitation);

        // Create notification for player
        $notification = new Notification();
        $notification->setUser($player->getUser());
        $notification->setMessage(sprintf(
            '%s has invited you to join team "%s". Click here to respond.',
            $team->getName(),
            $team->getName()
        ));

        $entityManager->persist($notification);
        $entityManager->flush();

        $this->addFlash('success', sprintf('Invitation sent to %s!', $player->getNickname()));

        return $this->redirectToRoute('app_organization_back', ['view' => 'players']);
    }

    #[Route('/{id}/delete', name: 'app_organization_delete', methods: ['POST'])]
    public function delete(Request $request, Organization $organization, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$organization->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($organization);
            $entityManager->flush();
            $this->addFlash('success', 'Organization deleted successfully.');
        }

        return $this->redirectToRoute('app_organization_back', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/team/{id}', name: 'app_organization_team_detail', methods: ['GET'])]
    public function teamDetail(
        int $id,
        TeamRepository $teamRepository,
        OrganizationRepository $organizationRepository,
        PlayerRepository $playerRepository
    ): Response {
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('You must be logged in.');
        }

        $team = $teamRepository->find($id);
        if (!$team) {
            throw $this->createNotFoundException('Team not found.');
        }

        $organization = $organizationRepository->findOneBy(['owner' => $user]);
        if (!$organization || $team->getOrganization() !== $organization) {
            throw $this->createAccessDeniedException('This team does not belong to your organization.');
        }

        $players = $playerRepository->findByTeam($team);
        
        // Calculate team stats
        $totalMatches = 0;
        $totalWins = 0;
        $totalKills = 0;
        $totalDeaths = 0;
        
        foreach ($players as $player) {
            foreach ($player->getStatistics() as $stat) {
                $totalMatches += $stat->getMatchesPlayed();
                $totalWins += $stat->getWins();
                $totalKills += $stat->getKills();
                $totalDeaths += $stat->getDeaths();
            }
        }
        
        $winRate = $totalMatches > 0 ? round(($totalWins / $totalMatches) * 100, 1) : 0;
        $kdRatio = $totalDeaths > 0 ? round($totalKills / $totalDeaths, 2) : 0;

        return $this->render('organization/team_detail.html.twig', [
            'team' => $team,
            'players' => $players,
            'organization' => $organization,
            'stats' => [
                'totalMatches' => $totalMatches,
                'totalWins' => $totalWins,
                'winRate' => $winRate,
                'kdRatio' => $kdRatio,
                'playerCount' => count($players),
            ],
        ]);
    }

    #[Route('/release/{playerId}', name: 'app_organization_release_player', methods: ['POST'])]
    public function releasePlayer(
        int $playerId,
        Request $request,
        OrganizationRepository $organizationRepository,
        PlayerRepository $playerRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('You must be logged in.');
        }

        $organization = $organizationRepository->findOneBy(['owner' => $user]);
        if (!$organization) {
            $this->addFlash('error', 'You must create an organization first.');
            return $this->redirectToRoute('app_organization_back', ['view' => 'dashboard']);
        }

        $player = $playerRepository->find($playerId);
        if (!$player) {
            $this->addFlash('error', 'Player not found.');
            return $this->redirectToRoute('app_organization_back', ['view' => 'players']);
        }

        $team = $player->getTeam();
        if (!$team || $team->getOrganization() !== $organization) {
            $this->addFlash('error', 'This player is not in your organization.');
            return $this->redirectToRoute('app_organization_back', ['view' => 'players']);
        }

        // Verify CSRF token
        if (!$this->isCsrfTokenValid('release' . $player->getId(), $request->getPayload()->getString('_token'))) {
            $this->addFlash('error', 'Invalid security token.');
            return $this->redirectToRoute('app_organization_team_detail', ['id' => $team->getId()]);
        }

        // Release player
        $player->setTeam(null);
        $entityManager->flush();

        $this->addFlash('success', sprintf('Player %s has been released from team %s.', $player->getNickname(), $team->getName()));

        return $this->redirectToRoute('app_organization_team_detail', ['id' => $team->getId()]);
    }

    #[Route('/ai/generate-description', name: 'app_organization_ai_generate_description', methods: ['POST'])]
    public function generateAIDescription(
        Request $request,
        OrganizationRepository $organizationRepository,
        AIService $aiService
    ): Response {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $organization = $organizationRepository->findOneBy(['owner' => $user]);
        if (!$organization) {
            return $this->json(['error' => 'Organization not found'], 404);
        }

        $description = $aiService->generateOrganizationDescription($organization->getName());
        
        return $this->json([
            'success' => true,
            'description' => $description,
            'ai_available' => $aiService->isAvailable()
        ]);
    }

    #[Route('/ai/generate-team-names', name: 'app_organization_ai_generate_team_names', methods: ['POST'])]
    public function generateAITeamNames(
        Request $request,
        OrganizationRepository $organizationRepository,
        AIService $aiService
    ): Response {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $organization = $organizationRepository->findOneBy(['owner' => $user]);
        if (!$organization) {
            return $this->json(['error' => 'Organization not found'], 404);
        }

        $teamNames = $aiService->generateTeamNames($organization->getName());
        
        return $this->json([
            'success' => true,
            'teamNames' => $teamNames,
            'ai_available' => $aiService->isAvailable()
        ]);
    }

    #[Route('/ai/generate-social-post', name: 'app_organization_ai_generate_social_post', methods: ['POST'])]
    public function generateSocialPost(
        Request $request,
        OrganizationRepository $organizationRepository,
        AIService $aiService
    ): Response {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $organization = $organizationRepository->findOneBy(['owner' => $user]);
        if (!$organization) {
            return $this->json(['error' => 'Organization not found'], 404);
        }

        $eventType = $request->request->get('eventType', 'announcement');
        $socialPost = $aiService->generateSocialMediaPost($organization->getName(), $eventType);
        
        return $this->json([
            'success' => true,
            'socialPost' => $socialPost,
            'ai_available' => $aiService->isAvailable()
        ]);
    }

    #[Route('/ai/create-team', name: 'app_organization_ai_create_team', methods: ['POST'])]
    public function createAITeam(
        Request $request,
        OrganizationRepository $organizationRepository,
        AITeamCreatorService $aiTeamCreator
    ): Response {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $organization = $organizationRepository->findOneBy(['owner' => $user]);
        if (!$organization) {
            return $this->json(['error' => 'Organization not found'], 404);
        }

        $gameId = $request->request->getInt('gameId');
        if (!$gameId) {
            return $this->json(['error' => 'Game ID is required'], 400);
        }

        try {
            $result = $aiTeamCreator->createAITeam($organization, $gameId);
            
            return $this->json([
                'success' => true,
                'team' => [
                    'id' => $result['team']->getId(),
                    'name' => $result['team']->getName(),
                    'description' => $result['team']->getDescription()
                ],
                'details' => $result['details'],
                'recruitedPlayers' => count($result['recruitedPlayers']),
                'ai_available' => $aiTeamCreator->isAvailable()
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/ai/get-games', name: 'app_organization_ai_get_games', methods: ['GET'])]
    public function getGamesForAITeam(
        OrganizationRepository $organizationRepository,
        AITeamCreatorService $aiTeamCreator,
        PlayerRepository $playerRepository
    ): Response {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $organization = $organizationRepository->findOneBy(['owner' => $user]);
        if (!$organization) {
            return $this->json(['error' => 'Organization not found'], 404);
        }

        $games = $aiTeamCreator->getAvailableGames();
        $gamesData = [];
        
        foreach ($games as $game) {
            // Count only available players (not already in teams)
            $availablePlayers = $playerRepository->findBy(['game' => $game, 'team' => null]);
            $gamesData[] = [
                'id' => $game->getId(),
                'name' => $game->getName(),
                'playerCount' => count($availablePlayers)
            ];
        }
        
        return $this->json([
            'success' => true,
            'games' => $gamesData,
            'ai_available' => $aiTeamCreator->isAvailable()
        ]);
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
}
