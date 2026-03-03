<?php

namespace App\Controller;

use App\Entity\ForumPost;
use App\Entity\Like;
use App\Repository\ForumPostRepository;
use App\Repository\LikeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Csrf\CsrfToken;

#[Route('/like')]
#[IsGranted('ROLE_USER')]
class LikeController extends AbstractController
{
    #[Route('/toggle/{postId}/{type}', name: 'app_like_toggle', methods: ['POST'])]
    public function toggleLike(
        int $postId,
        string $type,
        Request $request,
        ForumPostRepository $postRepository,
        LikeRepository $likeRepository,
        EntityManagerInterface $entityManager,
        CsrfTokenManagerInterface $csrfTokenManager
    ): JsonResponse {
        // Validate CSRF token
        $csrfToken = $request->headers->get('X-CSRF-Token');
        if (!$csrfToken || !$csrfTokenManager->isTokenValid(new CsrfToken('like_action', $csrfToken))) {
            return new JsonResponse(['error' => 'Invalid CSRF token'], 403);
        }

        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Unauthorized'], 401);
        }

        $post = $postRepository->find($postId);
        if (!$post) {
            return new JsonResponse(['error' => 'Post not found'], 404);
        }

        if (!\in_array($type, ['like', 'dislike'], true)) {
            return new JsonResponse(['error' => 'Invalid type'], 400);
        }

        $existingLike = $likeRepository->findByUserAndPost($user, $post);

        if ($existingLike) {
            if ($existingLike->getType() === $type) {
                // Remove the like/dislike if clicking the same type
                $entityManager->remove($existingLike);
                $entityManager->flush();

                return new JsonResponse([
                    'success' => true,
                    'action' => 'removed',
                    'likes' => $likeRepository->countLikesByPost($post),
                    'dislikes' => $likeRepository->countDislikesByPost($post),
                    'userLike' => null,
                ]);
            } else {
                // Change the type
                $existingLike->setType($type);
                $entityManager->flush();

                return new JsonResponse([
                    'success' => true,
                    'action' => 'changed',
                    'likes' => $likeRepository->countLikesByPost($post),
                    'dislikes' => $likeRepository->countDislikesByPost($post),
                    'userLike' => $type,
                ]);
            }
        }

        // Create new like/dislike
        $like = new Like();
        $like->setUser($user);
        $like->setPost($post);
        $like->setType($type);

        $entityManager->persist($like);
        $entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'action' => 'added',
            'likes' => $likeRepository->countLikesByPost($post),
            'dislikes' => $likeRepository->countDislikesByPost($post),
            'userLike' => $type,
        ]);
    }

    #[Route('/counts/{postId}', name: 'app_like_counts', methods: ['GET'])]
    public function getCounts(
        int $postId,
        ForumPostRepository $postRepository,
        LikeRepository $likeRepository
    ): JsonResponse {
        $post = $postRepository->find($postId);
        if (!$post) {
            return new JsonResponse(['error' => 'Post not found'], 404);
        }

        $user = $this->getUser();
        $userLike = null;

        if ($user) {
            $like = $likeRepository->findByUserAndPost($user, $post);
            if ($like) {
                $userLike = $like->getType();
            }
        }

        return new JsonResponse([
            'likes' => $likeRepository->countLikesByPost($post),
            'dislikes' => $likeRepository->countDislikesByPost($post),
            'userLike' => $userLike,
        ]);
    }
}
