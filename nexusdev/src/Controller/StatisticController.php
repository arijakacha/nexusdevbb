<?php

namespace App\Controller;

use App\Entity\Statistic;
use App\Form\StatisticType;
use App\Repository\StatisticRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/BStatistic')]
final class StatisticController extends AbstractController
{
    #[Route(name: 'app_statistic_back', methods: ['GET', 'POST'])]
    public function back(
        Request $request,
        StatisticRepository $statisticRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $statistics = $statisticRepository->findAll();

        $statId = $request->query->getInt('id', 0);
        if ($statId > 0) {
            $statistic = $statisticRepository->find($statId);
            if (!$statistic) {
                throw $this->createNotFoundException('Statistic not found');
            }
        } else {
            $statistic = new Statistic();
        }

        $form = $this->createForm(StatisticType::class, $statistic);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $isNew = $statistic->getId() === null;
            if ($isNew) {
                $entityManager->persist($statistic);
            }
            $entityManager->flush();

            $this->addFlash('success', $isNew ? 'Statistic created successfully.' : 'Statistic updated successfully.');

            return $this->redirectToRoute('app_statistic_back', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('statistic/back.html.twig', [
            'statistics' => $statistics,
            'form' => $form,
            'editing' => $statistic->getId() !== null,
            'currentStatistic' => $statistic,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_statistic_delete', methods: ['POST'])]
    public function delete(Request $request, Statistic $statistic, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$statistic->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($statistic);
            $entityManager->flush();
            $this->addFlash('success', 'Statistic deleted successfully.');
        }

        return $this->redirectToRoute('app_statistic_back', [], Response::HTTP_SEE_OTHER);
    }
}
