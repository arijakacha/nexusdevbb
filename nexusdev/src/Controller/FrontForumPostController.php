<?php

namespace App\Controller;

use App\Entity\ForumPost;
use App\Entity\Reponse;
use App\Repository\ForumPostRepository;
use App\Repository\LikeRepository;
use App\Repository\ReponseRepository;
use App\Service\DocumentAIService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

#[Route('/FForumPost')]
final class FrontForumPostController extends AbstractController
{
    #[Route(name: 'front_forum_post_index', methods: ['GET'])]
    public function index(
        ForumPostRepository $forumPostRepository,
        LikeRepository $likeRepository,
        PaginatorInterface $paginator,
        Request $request
    ): Response {
        $qb = $forumPostRepository->createQueryBuilder('fp')
            ->leftJoin('fp.author', 'a')
            ->addSelect('a');

        // Search filter
        $search = $request->query->get('search');
        $sortBy = $request->query->get('sortBy', 'createdAt');
        $sortOrder = $request->query->get('sortOrder', 'DESC');

        if ($search) {
            $qb->andWhere('fp.title LIKE :search OR fp.content LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        // Sorting
        $allowedSortFields = ['createdAt', 'title'];
        if (\in_array($sortBy, $allowedSortFields, true)) {
            $qb->orderBy('fp.' . $sortBy, strtoupper($sortOrder) === 'DESC' ? 'DESC' : 'ASC');
        } else {
            $qb->orderBy('fp.createdAt', 'DESC');
        }
        
        $pagination = $paginator->paginate(
            $qb,
            $request->query->getInt('page', 1),
            12
        );
        
        // Add like/dislike counts to each post
        foreach ($pagination as $post) {
            $post->likes = $likeRepository->countLikesByPost($post);
            $post->dislikes = $likeRepository->countDislikesByPost($post);
        }
        
        return $this->render('front/forumpost/index.html.twig', [
            'forumPosts' => $pagination,
            'pagination' => $pagination,
        ]);
    }

    #[Route('/{id}', name: 'front_forum_post_show', methods: ['GET', 'POST'])]
    public function show(
        ForumPost $forumPost,
        Request $request,
        EntityManagerInterface $entityManager,
        ForumPostRepository $forumPostRepository,
        LikeRepository $likeRepository
    ): Response {
        $user = $this->getUser();
        $commentForm = null;

        if ($user) {
            $reponse = new Reponse();
            $reponse->setAuthor($user);
            $reponse->setPost($forumPost);

            $formBuilder = $this->createFormBuilder($reponse);
            $formBuilder->add('content', TextareaType::class, [
                'label' => 'Add a comment',
                'constraints' => [
                    new NotBlank(['message' => 'Comment cannot be empty']),
                    new Length([
                        'min' => 3,
                        'max' => 1000,
                        'minMessage' => 'Comment must be at least {{ limit }} characters',
                        'maxMessage' => 'Comment cannot exceed {{ limit }} characters',
                    ]),
                ],
                'attr' => [
                    'rows' => 4,
                    'class' => 'form-control',
                    'placeholder' => 'Share your thoughts...'
                ],
            ]);

            $commentForm = $formBuilder->getForm();
            $commentForm->handleRequest($request);

            if ($commentForm->isSubmitted() && $commentForm->isValid()) {
                $entityManager->persist($reponse);
                $entityManager->flush();

                $this->addFlash('success', 'Comment added to the discussion.');

                return $this->redirectToRoute('front_forum_post_show', ['id' => $forumPost->getId()]);
            }
        }

        $popularPosts = $forumPostRepository->findBy([], ['createdAt' => 'DESC'], 5);
        
        // Get like/dislike counts
        $likes = $likeRepository->countLikesByPost($forumPost);
        $dislikes = $likeRepository->countDislikesByPost($forumPost);

        return $this->render('front/forumpost/show.html.twig', [
            'post' => $forumPost,
            'popularPosts' => $popularPosts,
            'commentForm' => $commentForm ? $commentForm->createView() : null,
            'likes' => $likes,
            'dislikes' => $dislikes,
        ]);
    }

    #[Route('/{id}/translate', name: 'front_forum_post_translate', methods: ['POST'])]
    public function translate(
        ForumPost $forumPost,
        Request $request,
        DocumentAIService $documentAIService
    ): JsonResponse {
        $targetLang = $request->request->get('targetLang', 'en');
        $content = $forumPost->getContent();

        try {
            $translatedContent = $documentAIService->translate($content, $targetLang);
            return new JsonResponse([
                'success' => true,
                'translatedContent' => $translatedContent,
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Translation failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    #[Route('/comment/{commentId}/translate', name: 'front_forum_comment_translate', methods: ['POST'])]
    public function translateComment(
        int $commentId,
        Request $request,
        DocumentAIService $documentAIService,
        ReponseRepository $reponseRepository
    ): JsonResponse {
        $comment = $reponseRepository->find($commentId);
        if (!$comment) {
            return new JsonResponse(['success' => false, 'error' => 'Comment not found'], 404);
        }

        $targetLang = $request->request->get('targetLang', 'en');
        $content = $comment->getContent();

        try {
            $translatedContent = $documentAIService->translate($content, $targetLang);
            return new JsonResponse([
                'success' => true,
                'translatedContent' => $translatedContent,
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Translation failed: ' . $e->getMessage(),
            ], 500);
        }
    }
}
