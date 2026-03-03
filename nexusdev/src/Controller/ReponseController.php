<?php

namespace App\Controller;

use App\Entity\Reponse;
use App\Entity\ForumPost;
use App\Repository\ForumPostRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/reponse')]
#[IsGranted('ROLE_ADMIN')]
class ReponseController extends AbstractController
{
    #[Route('/{id}/edit', name: 'app_reponse_edit', methods: ['POST'])]
    public function edit(Request $request, Reponse $reponse, EntityManagerInterface $entityManager): Response
    {
        $content = $request->request->get('content');
        
        if ($content) {
            $reponse->setContent($content);
            $entityManager->flush();
            $this->addFlash('success', 'Comment updated successfully.');
        }

        return $this->redirectToRoute('app_forum_post_back', ['id' => $reponse->getPost()->getId()]);
    }

    #[Route('/{id}/delete', name: 'app_reponse_delete', methods: ['POST'])]
    public function delete(Request $request, Reponse $reponse, EntityManagerInterface $entityManager): Response
    {
        $postId = $reponse->getPost()->getId();
        
        if ($this->isCsrfTokenValid('delete' . $reponse->getId(), $request->request->get('_token'))) {
            $entityManager->remove($reponse);
            $entityManager->flush();
            $this->addFlash('success', 'Comment deleted successfully.');
        }

        return $this->redirectToRoute('app_forum_post_back', ['id' => $postId]);
    }

    #[Route('/post/{id}/add', name: 'app_reponse_add', methods: ['POST'])]
    public function add(Request $request, ForumPost $post, EntityManagerInterface $entityManager): Response
    {
        $content = $request->request->get('content');
        
        if ($content) {
            $reponse = new Reponse();
            $reponse->setContent($content);
            $reponse->setPost($post);
            $reponse->setAuthor($this->getUser());
            $entityManager->persist($reponse);
            $entityManager->flush();
            $this->addFlash('success', 'Comment added successfully.');
        }

        return $this->redirectToRoute('app_forum_post_back', ['id' => $post->getId()]);
    }
}
