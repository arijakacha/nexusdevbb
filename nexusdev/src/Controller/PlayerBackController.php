<?php

namespace App\Controller;

use App\Entity\Player;
use App\Repository\CoachingSessionRepository;
use App\Repository\PlayerRepository;
use App\Repository\StatisticRepository;
use App\Repository\TeamRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/BPlayer')]
final class PlayerBackController extends AbstractController
{
    #[Route(name: 'app_player_back', methods: ['GET'])]
    public function dashboard(
        PlayerRepository $playerRepository,
        CoachingSessionRepository $coachingSessionRepository,
        StatisticRepository $statisticRepository
    ): Response {
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('You must be logged in.');
        }

        // Get player profile for this user (if exists)
        $player = $playerRepository->findOneBy(['id' => $user->getId()]);
        
        if (!$player) {
            // Show creation prompt
            return $this->render('player_back/no_profile.html.twig', [
                'user' => $user,
            ]);
        }

        // Get coaching sessions
        $upcomingSessions = $coachingSessionRepository->findUpcomingSessionsForPlayer($player);
        $pastSessions = $coachingSessionRepository->findPastSessionsForPlayer($player, 5);
        $totalSessions = $coachingSessionRepository->countTotalSessionsForPlayer($player);

        // Get statistics
        $statistics = $statisticRepository->findBy(['player' => $player]);
        
        // Calculate career stats
        $totalMatches = 0;
        $totalWins = 0;
        $totalKills = 0;
        $totalDeaths = 0;
        $totalAssists = 0;
        
        foreach ($statistics as $stat) {
            $totalMatches += $stat->getMatchesPlayed();
            $totalWins += $stat->getWins();
            $totalKills += $stat->getKills();
            $totalDeaths += $stat->getDeaths();
            $totalAssists += $stat->getAssists();
        }

        $careerStats = [
            'totalMatches' => $totalMatches,
            'totalWins' => $totalWins,
            'winRate' => $totalMatches > 0 ? round(($totalWins / $totalMatches) * 100, 1) : 0,
            'kdRatio' => $totalDeaths > 0 ? round($totalKills / $totalDeaths, 2) : 0,
            'kda' => $totalDeaths > 0 ? round(($totalKills + $totalAssists) / $totalDeaths, 2) : 0,
        ];

        return $this->render('player_back/dashboard.html.twig', [
            'player' => $player,
            'upcomingSessions' => $upcomingSessions,
            'pastSessions' => $pastSessions,
            'totalSessions' => $totalSessions,
            'statistics' => $statistics,
            'careerStats' => $careerStats,
        ]);
    }

    #[Route('/sessions', name: 'app_player_sessions', methods: ['GET'])]
    public function sessions(
        CoachingSessionRepository $coachingSessionRepository,
        PlayerRepository $playerRepository
    ): Response {
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('You must be logged in.');
        }

        $player = $playerRepository->findOneBy(['id' => $user->getId()]);
        if (!$player) {
            return $this->redirectToRoute('app_player_back');
        }

        $sessions = $coachingSessionRepository->findAllSessionsForPlayer($player);

        return $this->render('player_back/sessions.html.twig', [
            'player' => $player,
            'sessions' => $sessions,
        ]);
    }

    #[Route('/stats', name: 'app_player_stats', methods: ['GET'])]
    public function statistics(
        StatisticRepository $statisticRepository,
        PlayerRepository $playerRepository
    ): Response {
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('You must be logged in.');
        }

        $player = $playerRepository->findOneBy(['id' => $user->getId()]);
        if (!$player) {
            return $this->redirectToRoute('app_player_back');
        }

        $statistics = $statisticRepository->findBy(['player' => $player]);

        return $this->render('player_back/statistics.html.twig', [
            'player' => $player,
            'statistics' => $statistics,
        ]);
    }

    #[Route('/profile', name: 'app_player_profile', methods: ['GET', 'POST'])]
    public function profile(
        Request $request,
        PlayerRepository $playerRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('You must be logged in.');
        }

        $player = $playerRepository->findOneBy(['id' => $user->getId()]);
        if (!$player) {
            return $this->redirectToRoute('app_player_back');
        }

        // Create simple form for player profile editing
        $form = $this->createFormBuilder($player)
            ->add('nickname', null, ['label' => 'Nickname'])
            ->add('realName', null, ['label' => 'Real Name', 'required' => false])
            ->add('role', null, ['label' => 'Role/Position', 'required' => false])
            ->add('nationality', null, ['label' => 'Nationality', 'required' => false])
            ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Profile updated successfully.');
            return $this->redirectToRoute('app_player_profile');
        }

        return $this->render('player_back/profile.html.twig', [
            'player' => $player,
            'form' => $form->createView(),
        ]);
    }
}
