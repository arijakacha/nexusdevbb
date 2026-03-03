<?php

namespace App\Controller;

use App\Entity\Game;
use App\Form\GameType;
use App\Repository\GameRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/BGame')]
final class GameController extends AbstractController
{
    #[Route(name: 'app_game_back', methods: ['GET', 'POST'])]
    public function back(
        Request $request,
        GameRepository $gameRepository,
        EntityManagerInterface $entityManager,
        PaginatorInterface $paginator
    ): Response {
        $qb = $gameRepository->createQueryBuilder('g')
            ->select('g');

        $search = $request->query->get('search');
        if ($search) {
            $qb->andWhere('g.name LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        // Sorting
        $sort = $request->query->get('sort', 'id');
        $direction = $request->query->get('direction', 'ASC');
        
        $allowedSorts = ['id', 'name'];
        $allowedDirections = ['ASC', 'DESC'];
        
        if (!in_array($sort, $allowedSorts)) {
            $sort = 'id';
        }
        if (!in_array(strtoupper($direction), $allowedDirections)) {
            $direction = 'ASC';
        }
        
        $qb->orderBy('g.' . $sort, $direction);

        // Get results manually and create pagination array
        $query = $qb->getQuery();
        $results = $query->getResult();
        
        // Use paginator with array to bypass OrderByWalker
        $pagination = $paginator->paginate(
            $results,
            $request->query->getInt('page', 1),
            10
        );

        $gameId = $request->query->getInt('id', 0);
        if ($gameId > 0) {
            $game = $gameRepository->find($gameId);
            if (!$game) {
                throw $this->createNotFoundException('Game not found');
            }
        } else {
            $game = new Game();
        }

        $form = $this->createForm(GameType::class, $game);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $isNew = $game->getId() === null;
            if ($isNew) {
                $entityManager->persist($game);
            }
            $entityManager->flush();

            $this->addFlash('success', $isNew ? 'Game created successfully.' : 'Game updated successfully.');

            return $this->redirectToRoute('app_game_back', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('game/back.html.twig', [
            'pagination' => $pagination,
            'form' => $form,
            'editing' => $game->getId() !== null,
            'currentGame' => $game,
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_game_delete', methods: ['POST'])]
    public function delete(Request $request, Game $game, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$game->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($game);
            $entityManager->flush();
            $this->addFlash('success', 'Game deleted successfully.');
        }

        return $this->redirectToRoute('app_game_back', [], Response::HTTP_SEE_OTHER);
    }
}
