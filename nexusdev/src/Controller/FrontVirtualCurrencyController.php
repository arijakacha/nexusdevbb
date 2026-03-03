<?php

namespace App\Controller;

use App\Repository\VirtualCurrencyRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/FVirtualCurrency')]
final class FrontVirtualCurrencyController extends AbstractController
{
    #[Route(name: 'front_virtual_currency_index', methods: ['GET'])]
    public function index(VirtualCurrencyRepository $virtualCurrencyRepository): Response
    {
        return $this->render('front/virtual_currency/index.html.twig', [
            'virtualCurrencies' => $virtualCurrencyRepository->findAll(),
        ]);
    }
}
