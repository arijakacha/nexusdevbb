<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/')]
final class FrontHomeController extends AbstractController
{
    #[Route(name: 'front_home', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('front/home.html.twig');
    }
}
