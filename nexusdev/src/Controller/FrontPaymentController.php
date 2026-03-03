<?php

namespace App\Controller;

use App\Repository\PaymentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/FPayment')]
final class FrontPaymentController extends AbstractController
{
    #[Route(name: 'front_payment_index', methods: ['GET'])]
    public function index(PaymentRepository $paymentRepository): Response
    {
        return $this->render('front/payment/index.html.twig', [
            'payments' => $paymentRepository->findAll(),
        ]);
    }
}
