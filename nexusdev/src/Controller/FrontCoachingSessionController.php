<?php

namespace App\Controller;

use App\Entity\CoachingSession;
use App\Entity\Player;
use App\Entity\Coach;
use App\Repository\CoachRepository;
use App\Repository\PlayerRepository;
use App\Repository\CoachingSessionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/FCoaching')]
final class FrontCoachingSessionController extends AbstractController
{
    #[Route('/{id}', name: 'front_coaching_new', methods: ['GET', 'POST'])]
    public function new(
        int $id,
        Request $request,
        PlayerRepository $playerRepository,
        CoachRepository $coachRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $player = $playerRepository->find($id);
        if (!$player instanceof Player) {
            throw $this->createNotFoundException('Player not found');
        }

        $coaches = $coachRepository->findAll();

        $session = new CoachingSession();
        $session->setPlayer($player);

        $formBuilder = $this->createFormBuilder($session);
        $formBuilder
            ->add('coach', EntityType::class, [
                'class' => Coach::class,
                'choices' => $coaches,
                'choice_label' => function (Coach $coach) {
                    return $coach->getUser()->getUsername();
                },
            ])
            ->add('scheduledAt', DateTimeType::class, [
                'widget' => 'single_text',
            ]);

        $form = $formBuilder->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $session->setStatus('PENDING');
            $entityManager->persist($session);
            $entityManager->flush();

            $this->addFlash('success', 'Your coaching request has been sent.');

            return $this->redirectToRoute('front_player_game', ['id' => $player->getId()]);
        }

        return $this->render('front/coaching/new.html.twig', [
            'player' => $player,
            'form' => $form,
        ]);
    }

    #[Route('/my', name: 'front_coaching_my', methods: ['GET'])]
    #[IsGranted('ROLE_COACH')]
    public function mySessions(
        CoachingSessionRepository $coachingSessionRepository
    ): Response {
        $user = $this->getUser();
        if (!$user || !$user->getCoach()) {
            $this->addFlash('error', 'You do not have a coach profile.');
            return $this->redirectToRoute('front_home');
        }

        $coach = $user->getCoach();
        $sessions = $coachingSessionRepository->findBy(
            ['coach' => $coach],
            ['scheduledAt' => 'ASC']
        );

        return $this->render('front/coaching/my_sessions.html.twig', [
            'sessions' => $sessions,
        ]);
    }

    #[Route('/confirm/{id}', name: 'front_coaching_confirm', methods: ['POST'])]
    #[IsGranted('ROLE_COACH')]
    public function confirm(
        CoachingSession $session,
        EntityManagerInterface $entityManager
    ): Response {
        $user = $this->getUser();
        if (!$user || !$user->getCoach() || $session->getCoach() !== $user->getCoach()) {
            $this->addFlash('error', 'You can only confirm your own sessions.');
            return $this->redirectToRoute('front_coaching_my');
        }

        $session->setStatus('CONFIRMED');
        $entityManager->flush();

        $this->addFlash('success', 'Session confirmed.');
        return $this->redirectToRoute('front_coaching_my');
    }
}
