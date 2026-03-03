<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/BUser')]
final class UserController extends AbstractController
{
    #[Route(name: 'app_user_back', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function back(
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        PaginatorInterface $paginator
    ): Response {
        $qb = $userRepository->createQueryBuilder('u');
        
        // Multi-criteria search
        $search = $request->query->get('search');
        $status = $request->query->get('status');
        $userType = $request->query->get('userType');
        
        if ($search) {
            $qb->andWhere('u.username LIKE :search OR u.email LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }
        
        if ($status) {
            $qb->andWhere('u.status = :status')
               ->setParameter('status', $status);
        }
        
        if ($userType) {
            $qb->andWhere('u.userType = :userType')
               ->setParameter('userType', $userType);
        }
        
        // Sorting
        $sort = $request->query->get('sort', 'createdAt');
        $direction = $request->query->get('direction', 'DESC');
        
        $allowedSorts = ['id', 'username', 'email', 'createdAt', 'status', 'userType'];
        $allowedDirections = ['ASC', 'DESC'];
        
        if (!in_array($sort, $allowedSorts)) {
            $sort = 'createdAt';
        }
        if (!in_array(strtoupper($direction), $allowedDirections)) {
            $direction = 'DESC';
        }
        
        $qb->orderBy('u.' . $sort, $direction);

        // Get results manually and create pagination array
        $query = $qb->getQuery();
        $results = $query->getResult();
        
        // Use paginator with array to bypass OrderByWalker
        $pagination = $paginator->paginate(
            $results,
            $request->query->getInt('page', 1),
            10
        );

        $userId = $request->query->getInt('id', 0);
        if ($userId > 0) {
            $user = $userRepository->find($userId);
            if (!$user) {
                throw $this->createNotFoundException('User not found');
            }
        } else {
            $user = new User();
        }

        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $isNew = $user->getId() === null;
            if ($isNew) {
                $entityManager->persist($user);
            }
            $entityManager->flush();

            $this->addFlash('success', $isNew ? 'User created successfully.' : 'User updated successfully.');

            return $this->redirectToRoute('app_user_back', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('user/back.html.twig', [
            'pagination' => $pagination,
            'form' => $form,
            'editing' => $user->getId() !== null,
            'currentUser' => $user,
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_user_delete', methods: ['POST'])]
    public function delete(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($user);
            $entityManager->flush();
            $this->addFlash('success', 'User deleted successfully.');
        }

        return $this->redirectToRoute('app_user_back', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/toggle-status', name: 'app_user_toggle_status', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function toggleStatus(
        User $user,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        if ($this->isCsrfTokenValid('toggle-status'.$user->getId(), $request->getPayload()->getString('_token'))) {
            $newStatus = $user->getStatus() === 'ACTIVE' ? 'BANNED' : 'ACTIVE';
            $user->setStatus($newStatus);
            $entityManager->flush();

            $action = $newStatus === 'BANNED' ? 'blocked' : 'unblocked';
            $this->addFlash('success', sprintf('User %s has been %s.', $user->getUsername(), $action));
        }

        return $this->redirectToRoute('app_user_back', [], Response::HTTP_SEE_OTHER);
    }
}
