<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Content;
use App\Form\CommentType;
use App\Repository\CommentRepository;
use App\Repository\ContentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/BComment')]
final class CommentController extends AbstractController
{
    #[Route(name: 'app_comment_back', methods: ['GET', 'POST'])]
    public function back(
        Request $request,
        CommentRepository $commentRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $comments = $commentRepository->findAll();

        $commentId = $request->query->getInt('id', 0);
        if ($commentId > 0) {
            $comment = $commentRepository->find($commentId);
            if (!$comment) {
                throw $this->createNotFoundException('Comment not found');
            }
        } else {
            $comment = new Comment();
        }

        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $isNew = $comment->getId() === null;
            if ($isNew) {
                $entityManager->persist($comment);
            }
            $entityManager->flush();

            $this->addFlash('success', $isNew ? 'Comment created successfully.' : 'Comment updated successfully.');

            return $this->redirectToRoute('app_comment_back', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('comment/back.html.twig', [
            'comments' => $comments,
            'form' => $form,
            'editing' => $comment->getId() !== null,
            'currentComment' => $comment,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_comment_edit', methods: ['POST'])]
    public function edit(Request $request, Comment $comment, EntityManagerInterface $entityManager): Response
    {
        $content = $request->request->get('content');
        
        if ($content) {
            $comment->setContent($content);
            $entityManager->flush();
            $this->addFlash('success', 'Comment updated successfully.');
        }

        // Redirect back to the appropriate back office
        if ($comment->getGuide()) {
            return $this->redirectToRoute('app_content_back', ['id' => $comment->getGuide()->getId()]);
        } elseif ($comment->getPost()) {
            return $this->redirectToRoute('app_forum_post_back', ['id' => $comment->getPost()->getId()]);
        }

        return $this->redirectToRoute('app_comment_back');
    }

    #[Route('/{id}/delete', name: 'app_comment_delete', methods: ['POST'])]
    public function delete(Request $request, Comment $comment, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$comment->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($comment);
            $entityManager->flush();
            $this->addFlash('success', 'Comment deleted successfully.');
        }

        return $this->redirectToRoute('app_comment_back', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/content/{id}/add', name: 'app_content_comment_add', methods: ['POST'])]
    public function addToContent(Request $request, Content $content, EntityManagerInterface $entityManager): Response
    {
        $commentText = $request->request->get('content');
        
        if ($commentText) {
            $comment = new Comment();
            $comment->setContent($commentText);
            $comment->setGuide($content);
            $comment->setAuthor($this->getUser());
            $entityManager->persist($comment);
            $entityManager->flush();
            $this->addFlash('success', 'Comment added successfully.');
        }

        return $this->redirectToRoute('app_content_back', ['id' => $content->getId()]);
    }
}
