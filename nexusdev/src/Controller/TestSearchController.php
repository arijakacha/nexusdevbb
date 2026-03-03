<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/test')]
final class TestSearchController extends AbstractController
{
    public function __construct(private UserRepository $userRepository) {}

    #[Route('/search-users', name: 'test_search_users')]
    public function searchUsers(): JsonResponse
    {
        $users = $this->userRepository->createQueryBuilder('u')
            ->select('u.id', 'u.username')
            ->where('u.username LIKE :query')
            ->setParameter('query', 'a%')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        return new JsonResponse($users);
    }
}
