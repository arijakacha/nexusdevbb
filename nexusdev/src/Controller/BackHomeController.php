<?php

namespace App\Controller;

use App\Repository\UserRepository;
use App\Repository\PlayerRepository;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/BHome')]
final class BackHomeController extends AbstractController
{
    #[Route(name: 'back_home', methods: ['GET'])]
    public function index(
        UserRepository $userRepository,
        PlayerRepository $playerRepository,
        OrderRepository $orderRepository,
        ProductRepository $productRepository
    ): Response {
        $stats = [
            'total_users' => count($userRepository->findAll()),
            'active_players' => count($playerRepository->findAll()),
            'total_orders' => count($orderRepository->findAll()),
            'total_products' => count($productRepository->findAll()),
        ];

        return $this->render('back/home.html.twig', [
            'stats' => $stats,
        ]);
    }
}
