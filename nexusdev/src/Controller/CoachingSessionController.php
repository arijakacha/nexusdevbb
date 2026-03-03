<?php

namespace App\Controller;

use App\Entity\CoachingSession;
use App\Form\CoachingSessionType;
use App\Repository\CoachingSessionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/BCoachingSession')]
final class CoachingSessionController extends AbstractController
{
    #[Route(name: 'app_coaching_session_back', methods: ['GET', 'POST'])]
    public function back(
        Request $request,
        CoachingSessionRepository $coachingSessionRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $sessions = $coachingSessionRepository->findAll();

        $sessionId = $request->query->getInt('id', 0);
        if ($sessionId > 0) {
            $session = $coachingSessionRepository->find($sessionId);
            if (!$session) {
                throw $this->createNotFoundException('Coaching session not found');
            }
        } else {
            $session = new CoachingSession();
        }

        $form = $this->createForm(CoachingSessionType::class, $session);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $isNew = $session->getId() === null;
            if ($isNew) {
                $entityManager->persist($session);
            }
            $entityManager->flush();

            $this->addFlash('success', $isNew ? 'Coaching session created successfully.' : 'Coaching session updated successfully.');

            return $this->redirectToRoute('app_coaching_session_back', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('coaching_session/back.html.twig', [
            'sessions' => $sessions,
            'form' => $form,
            'editing' => $session->getId() !== null,
            'currentSession' => $session,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_coaching_session_delete', methods: ['POST'])]
    public function delete(Request $request, CoachingSession $session, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$session->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($session);
            $entityManager->flush();
            $this->addFlash('success', 'Coaching session deleted successfully.');
        }

        return $this->redirectToRoute('app_coaching_session_back', [], Response::HTTP_SEE_OTHER);
    }
}
