<?php

namespace App\Controller;

use App\Entity\Team;
use App\Form\TeamType;
use App\Repository\TeamRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/BTeam')]
final class TeamController extends AbstractController
{
    #[Route(name: 'app_team_back', methods: ['GET', 'POST'])]
    public function back(
        Request $request,
        TeamRepository $teamRepository,
        EntityManagerInterface $entityManager,
        PaginatorInterface $paginator
    ): Response {
        $qb = $teamRepository->createQueryBuilder('t');

        // Search filter
        $search = $request->query->get('search');
        if ($search) {
            $qb->andWhere('t.name LIKE :search')
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
        
        $qb->orderBy('t.' . $sort, $direction);

        // Get results manually and create pagination array
        $query = $qb->getQuery();
        $results = $query->getResult();
        
        // Use paginator with array to bypass OrderByWalker
        $pagination = $paginator->paginate(
            $results,
            $request->query->getInt('page', 1),
            10
        );

        $teamId = $request->query->getInt('id', 0);
        if ($teamId > 0) {
            $team = $teamRepository->find($teamId);
            if (!$team) {
                throw $this->createNotFoundException('Team not found');
            }
        } else {
            $team = new Team();
        }

        $form = $this->createForm(TeamType::class, $team);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $isNew = $team->getId() === null;
            if ($isNew) {
                $entityManager->persist($team);
            }
            $entityManager->flush();

            $this->addFlash('success', $isNew ? 'Team created successfully.' : 'Team updated successfully.');

            return $this->redirectToRoute('app_team_back', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('team/back.html.twig', [
            'pagination' => $pagination,
            'form' => $form,
            'editing' => $team->getId() !== null,
            'currentTeam' => $team,
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_team_delete', methods: ['POST'])]
    public function delete(Request $request, Team $team, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$team->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($team);
            $entityManager->flush();
            $this->addFlash('success', 'Team deleted successfully.');
        }

        return $this->redirectToRoute('app_team_back', [], Response::HTTP_SEE_OTHER);
    }
}
