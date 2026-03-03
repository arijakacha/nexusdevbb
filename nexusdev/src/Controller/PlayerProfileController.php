<?php

namespace App\Controller;

use App\Entity\Player;
use App\Entity\Statistic;
use App\Entity\User;
use App\Form\PlayerProfileSetupType;
use App\Repository\GameRepository;
use App\Repository\TeamInvitationRepository;
use App\Repository\TeamRepository;
use App\Repository\StatisticRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/player')]
final class PlayerProfileController extends AbstractController
{
    // Step 1: Select Game only
    #[Route('/become-player', name: 'app_player_become', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function becomePlayer(
        Request $request,
        GameRepository $gameRepository
    ): Response {
        $session = $request->getSession();

        // Check if user already has a player profile (session or user flag)
        $user = $this->getUser();
        if ($session->get('my_player_id')) {
            $this->addFlash('warning', 'You already have a player profile!');
            return $this->redirectToRoute('app_player_dashboard', ['id' => $session->get('my_player_id')]);
        }

        if ($user instanceof User && $user->hasPlayer()) {
            $this->addFlash('warning', 'You already have a player profile!');
            return $this->redirectToRoute('front_home');
        }
        
        if ($request->isMethod('POST')) {
            $gameId = $request->request->get('game_id');
            
            if ($gameId) {
                $request->getSession()->set('player_setup_game', $gameId);
                return $this->redirectToRoute('app_player_create_profile');
            }
        }
        
        return $this->render('player/become.html.twig', [
            'games' => $gameRepository->findAll(),
        ]);
    }
    
    // Step 2: Create Player Profile
    #[Route('/create-profile', name: 'app_player_create_profile', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function createProfile(
        Request $request,
        GameRepository $gameRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $session = $request->getSession();
        
        // Check if user already has a player profile
        if ($session->get('my_player_id')) {
            $this->addFlash('warning', 'You already have a player profile!');
            return $this->redirectToRoute('app_player_dashboard', ['id' => $session->get('my_player_id')]);
        }
        
        $gameId = $session->get('player_setup_game');
        
        if (!$gameId) {
            return $this->redirectToRoute('app_player_become');
        }
        
        $game = $gameRepository->find($gameId);
        
        if (!$game) {
            return $this->redirectToRoute('app_player_become');
        }
        
        $player = new Player();
        $player->setGame($game);
        $player->setScore(0);
        $player->setIsPro(false);
        
        $form = $this->createForm(PlayerProfileSetupType::class, $player);
        $form->handleRequest($request);
        
        if ($form->isSubmitted()) {
            error_log("Riot Edit: Form submitted");
            if (!$form->isValid()) {
                $errors = [];
                foreach ($form->getErrors(true) as $error) {
                    $errors[] = $error->getMessage();
                }
                error_log("Riot Edit: Form errors: " . implode(', ', $errors));
            }
        }
        
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($player);

            // Mark current user as having a player profile
            $user = $this->getUser();
            if ($user instanceof User) {
                $user->setHasPlayer(true);
                
                // Link player to user
                $player->setUser($user);

                // If user already has an avatar, sync it to the player profile
                if ($user->getProfilePicture()) {
                    $player->setProfilePicture($user->getProfilePicture());
                }
                
                // Handle Riot Games account fields - separate username and tag
                $riotGameName = $request->request->get('riot_game_name');
                $riotTagLine = $request->request->get('riot_tag_line');
                
                error_log("Riot Create: gameName='$riotGameName', tagLine='$riotTagLine'");
                
                if ($riotGameName) {
                    // Combine game name and tag
                    $tag = $riotTagLine ?: 'BWS';
                    $fullSummonerName = trim($riotGameName) . '#' . trim($tag);
                    $user->setRiotSummonerName($fullSummonerName);
                    $user->setRiotRegion('euw1');
                    error_log("Riot Create: Saved summonerName='$fullSummonerName'");
                } else {
                    error_log("Riot Create: No gameName provided!");
                }
            }

            $entityManager->flush();
            
            // Store player ID in session for ownership check
            $session->set('my_player_id', $player->getId());
            
            // Create empty statistics
            $statistic = new Statistic();
            $statistic->setPlayer($player);
            $statistic->setGame($game);
            $statistic->setMatchesPlayed(0);
            $statistic->setWins(0);
            $statistic->setLosses(0);
            $statistic->setKills(0);
            $statistic->setDeaths(0);
            $statistic->setAssists(0);
            $statistic->setWinRate('0.00');
            
            $entityManager->persist($statistic);
            $entityManager->flush();
            
            $session->remove('player_setup_game');
            
            $this->addFlash('success', 'Welcome to the arena! Your player profile is ready.');
            
            return $this->redirectToRoute('app_player_dashboard', ['id' => $player->getId()]);
        }
        
        return $this->render('player/create_profile.html.twig', [
            'form' => $form,
            'game' => $game,
        ]);
    }
    
    // Player Dashboard
    #[Route('/dashboard/{id}', name: 'app_player_dashboard', methods: ['GET'])]
    public function dashboard(
        Player $player, 
        StatisticRepository $statisticRepository,
        GameRepository $gameRepository,
        TeamInvitationRepository $invitationRepository,
        Request $request
    ): Response {
        $session = $request->getSession();
        
        // Find League of Legends statistic (for Riot API rank)
        $lolGame = $gameRepository->findOneBy(['name' => 'League of Legends']);
        $statistic = null;
        if ($lolGame) {
            $statistic = $statisticRepository->findOneBy([
                'player' => $player,
                'game' => $lolGame,
            ]);
        }
        
        // Fallback to player's game statistic if no LoL stats
        if (!$statistic) {
            $statistic = $statisticRepository->findOneBy([
                'player' => $player,
                'game' => $player->getGame(),
            ]);
        }
        
        $isOwner = $this->isPlayerOwner($player, $session);

        // Ensure session remembers this player as the current user's profile
        if ($isOwner && $session->get('my_player_id') !== $player->getId()) {
            $session->set('my_player_id', $player->getId());
        }
        
        // Get pending team invitations for this player
        $pendingInvitations = $invitationRepository->findPendingForPlayer($player);
        
        return $this->render('player/dashboard.html.twig', [
            'player' => $player,
            'statistic' => $statistic,
            'isOwner' => $isOwner,
            'pendingInvitations' => $pendingInvitations,
        ]);
    }
    
    // Edit Player Profile - Only owner
    #[Route('/edit/{id}', name: 'app_player_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function edit(
        Request $request,
        Player $player,
        EntityManagerInterface $entityManager
    ): Response {
        if (!$this->isPlayerOwner($player, $request->getSession())) {
            throw $this->createAccessDeniedException('You can only edit your own profile.');
        }
        
        $form = $this->createForm(PlayerProfileSetupType::class, $player);
        $form->handleRequest($request);
        
        if ($form->isSubmitted()) {
            error_log("Riot Edit: Form submitted");
            if (!$form->isValid()) {
                $errors = [];
                foreach ($form->getErrors(true) as $error) {
                    $errors[] = $error->getMessage();
                }
                error_log("Riot Edit: Form errors: " . implode(', ', $errors));
            }
        }
        
        if ($form->isSubmitted() && $form->isValid()) {
            // Handle Riot Games account fields - separate username and tag
            $riotGameName = $request->request->get('riot_game_name');
            $riotTagLine = $request->request->get('riot_tag_line');
            
            error_log("Riot Edit: gameName='$riotGameName', tagLine='$riotTagLine'");
            
            $user = $this->getUser();
            if ($user instanceof User) {
                if ($riotGameName) {
                    // Combine game name and tag
                    $tag = $riotTagLine ?: 'BWS';
                    $fullSummonerName = trim($riotGameName) . '#' . trim($tag);
                    $user->setRiotSummonerName($fullSummonerName);
                    error_log("Riot Edit: Saved summonerName='$fullSummonerName'");
                } else {
                    error_log("Riot Edit: No gameName provided!");
                }
                $user->setRiotRegion('euw1');
            }
            
            $entityManager->flush();
            
            if ($riotGameName) {
                $this->addFlash('success', 'Player profile and Riot account updated!');
            } else {
                $this->addFlash('success', 'Player profile updated!');
            }
            
            return $this->redirectToRoute('app_player_dashboard', ['id' => $player->getId()]);
        }
        
        return $this->render('player/edit.html.twig', [
            'form' => $form,
            'player' => $player,
        ]);
    }
    
    // Classify/Update Stats - Only owner (after playing tournaments)
    #[Route('/classify/{id}', name: 'app_player_classify', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function classify(
        Request $request,
        Player $player,
        StatisticRepository $statisticRepository,
        EntityManagerInterface $entityManager
    ): Response {
        if (!$this->isPlayerOwner($player, $request->getSession())) {
            $this->addFlash('error', 'You can only update your own player statistics.');
            return $this->redirectToRoute('app_player_dashboard', ['id' => $player->getId()]);
        }
        
        $statistic = $statisticRepository->findOneBy(['player' => $player]);
        
        if (!$statistic) {
            // Create statistics if they don't exist
            $statistic = new Statistic();
            $statistic->setPlayer($player);
            $statistic->setGame($player->getGame());
            $statistic->setMatchesPlayed(0);
            $statistic->setWins(0);
            $statistic->setLosses(0);
            $statistic->setKills(0);
            $statistic->setDeaths(0);
            $statistic->setAssists(0);
            $statistic->setWinRate('0.00');
            $entityManager->persist($statistic);
        }
        
        // Update stats from tournament results
        $wins = (int) $request->request->get('wins', 0);
        $losses = (int) $request->request->get('losses', 0);
        $kills = (int) $request->request->get('kills', 0);
        $deaths = (int) $request->request->get('deaths', 0);
        $assists = (int) $request->request->get('assists', 0);
        
        // Calculate matches as wins + losses
        $matches = $wins + $losses;
        
        // Validate that at least some data was submitted
        if ($wins === 0 && $losses === 0 && $kills === 0) {
            $this->addFlash('warning', 'No statistics to update. Please enter your match results.');
            return $this->redirectToRoute('app_player_dashboard', ['id' => $player->getId()]);
        }
        
        // Add to existing stats (cumulative)
        $newWins = $statistic->getWins() + $wins;
        $newLosses = $statistic->getLosses() + $losses;
        $totalMatches = $newWins + $newLosses;
        
        $statistic->setWins($newWins);
        $statistic->setLosses($newLosses);
        $statistic->setMatchesPlayed($totalMatches);
        $statistic->setKills($statistic->getKills() + $kills);
        $statistic->setDeaths($statistic->getDeaths() + $deaths);
        $statistic->setAssists($statistic->getAssists() + $assists);
        
        // Calculate win rate formula: (wins / totalMatches) * 100
        if ($totalMatches > 0) {
            $winRate = round(($newWins / $totalMatches) * 100, 2);
            $statistic->setWinRate((string) $winRate);
        }
        
        // Calculate score based on performance
        $score = ($statistic->getWins() * 10) 
               + ($statistic->getKills() * 2) 
               + ($statistic->getAssists() * 1) 
               - ($statistic->getDeaths() * 1);
        
        $player->setScore(max(0, $score));
        
        // Auto-promote to PRO if score > 100
        if ($player->getScore() > 100 && !$player->isPro()) {
            $player->setIsPro(true);
            $this->addFlash('success', '🎉 Congratulations! You have been promoted to PRO status!');
        }
        
        $entityManager->flush();
        
        $this->addFlash('success', sprintf(
            'Tournament results recorded! Matches: +%d, Wins: +%d, Kills: +%d. Your rank is now: %s',
            $matches,
            $wins,
            $kills,
            $player->getRank()
        ));
        
        return $this->redirectToRoute('app_player_dashboard', ['id' => $player->getId()]);
    }

    #[Route('/pro-test/{id}', name: 'app_player_pro_test', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function proTest(
        Request $request,
        Player $player,
        EntityManagerInterface $entityManager
    ): Response {
        if (!$this->isPlayerOwner($player, $request->getSession())) {
            $this->addFlash('error', 'You can only take the PRO test for your own player profile.');
            return $this->redirectToRoute('app_player_dashboard', ['id' => $player->getId()]);
        }

        if ($player->isPro()) {
            $this->addFlash('info', 'You are already a PRO player.');
            return $this->redirectToRoute('app_player_dashboard', ['id' => $player->getId()]);
        }

        $formBuilder = $this->createFormBuilder();
        $formBuilder
            ->add('mechanics', IntegerType::class, [
                'label' => 'Aim & mechanics (0-50)',
                'attr' => ['min' => 0, 'max' => 50],
            ])
            ->add('gameSense', IntegerType::class, [
                'label' => 'Game sense & strategy (0-50)',
                'attr' => ['min' => 0, 'max' => 50],
            ])
            ->add('teamplay', IntegerType::class, [
                'label' => 'Team play & communication (0-50)',
                'attr' => ['min' => 0, 'max' => 50],
            ]);

        $form = $formBuilder->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $score = (int) $data['mechanics'] + (int) $data['gameSense'] + (int) $data['teamplay'];

            // Ensure we at least keep this score as a base
            if ($player->getScore() < $score) {
                $player->setScore($score);
            }

            if ($score > 100) {
                $player->setIsPro(true);
                $entityManager->flush();

                $this->addFlash('success', 'You passed the PRO test! You are now a PRO player.');
                return $this->redirectToRoute('app_player_dashboard', ['id' => $player->getId()]);
            }

            return $this->render('player/pro_result.html.twig', [
                'player' => $player,
                'score' => $score,
            ]);
        }

        return $this->render('player/pro_test.html.twig', [
            'player' => $player,
            'form' => $form,
        ]);
    }

    // Helper to check if current user owns this player profile
    private function isPlayerOwner(Player $player, $session): bool
    {
        return $session->get('my_player_id') === $player->getId();
    }
}
