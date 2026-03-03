<?php

namespace App\Controller;

use App\Entity\Player;
use App\Repository\PlayerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/FPlayerGame')]
final class FrontPlayerGameController extends AbstractController
{
    #[Route('/{id}', name: 'front_player_game', methods: ['GET', 'POST'])]
    public function play(
        int $id,
        Request $request,
        PlayerRepository $playerRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $player = $playerRepository->find($id);
        if (!$player instanceof Player) {
            throw $this->createNotFoundException('Player not found');
        }

        $formBuilder = $this->createFormBuilder(['score' => $player->getScore()]);
        $formBuilder->add('score', IntegerType::class, [
            'label' => 'Points earned in the game',
            'attr' => [
                'min' => 0,
                'step' => 1,
            ],
        ]);
        $form = $formBuilder->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $score = (int) ($data['score'] ?? 0);
            $player->setScore($score);
            $player->setIsPro($score >= 100);

            $entityManager->flush();

            if ($player->isPro()) {
                $this->addFlash('success', 'This player is now classified as PRO. Organizations can recruit them and open live streams.');
            } else {
                $this->addFlash('info', 'Score is below 100 points. We recommend booking a coaching session and checking guides to improve.');
            }

            return $this->redirectToRoute('front_player_game', ['id' => $player->getId()]);
        }

        return $this->render('front/player/game.html.twig', [
            'player' => $player,
            'form' => $form,
        ]);
    }
}
