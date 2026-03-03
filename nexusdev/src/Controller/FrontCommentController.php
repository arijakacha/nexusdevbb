<?php

namespace App\Controller;

use App\Repository\CommentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/FComment')]
final class FrontCommentController extends AbstractController
{
    #[Route(name: 'front_comment_index', methods: ['GET'])]
    public function index(CommentRepository $commentRepository): Response
    {
        return $this->render('front/comment/index.html.twig', [
            'comments' => $commentRepository->findAll(),
        ]);
    }
}
