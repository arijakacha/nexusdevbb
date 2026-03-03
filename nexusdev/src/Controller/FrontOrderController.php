<?php

namespace App\Controller;

use App\Repository\OrderRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/FOrder')]
final class FrontOrderController extends AbstractController
{
    #[Route(name: 'front_order_index', methods: ['GET'])]
    public function index(OrderRepository $orderRepository): Response
    {
        return $this->render('front/order/index.html.twig', [
            'orders' => $orderRepository->findAll(),
        ]);
    }
}
