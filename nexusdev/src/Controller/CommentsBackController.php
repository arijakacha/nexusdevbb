<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Reponse;
use App\Entity\ForumPost;
use App\Entity\Content;
use App\Repository\ForumPostRepository;
use App\Repository\ContentRepository;
use App\Repository\ReponseRepository;
use App\Repository\CommentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/BComments')]
#[IsGranted('ROLE_ADMIN')]
class CommentsBackController extends AbstractController
{
    #[Route(name: 'app_comments_back', methods: ['GET'])]
    public function index(
        Request $request,
        ReponseRepository $reponseRepository,
        CommentRepository $commentRepository,
        PaginatorInterface $paginator
    ): Response {
        $currentTab = $request->query->get('tab', 'forum');

        // Sorting
        $sort = $request->query->get('sort', 'createdAt');
        $direction = $request->query->get('direction', 'DESC');
        
        $allowedSorts = ['id', 'content', 'createdAt'];
        $allowedDirections = ['ASC', 'DESC'];
        
        if (!in_array($sort, $allowedSorts)) {
            $sort = 'createdAt';
        }
        if (!in_array(strtoupper($direction), $allowedDirections)) {
            $direction = 'DESC';
        }

        if ($currentTab === 'forum') {
            $qb = $reponseRepository->createQueryBuilder('r')
                ->orderBy('r.' . $sort, $direction);
            
            // Get results manually and create pagination array
            $query = $qb->getQuery();
            $results = $query->getResult();
            
            // Use paginator with array to bypass OrderByWalker
            $forumComments = $paginator->paginate(
                $results,
                $request->query->getInt('page', 1),
                10
            );
            
            $contentComments = [];
        } else {
            $qb = $commentRepository->createQueryBuilder('c')
                ->orderBy('c.' . $sort, $direction);
            
            // Get results manually and create pagination array
            $query = $qb->getQuery();
            $results = $query->getResult();
            
            // Use paginator with array to bypass OrderByWalker
            $contentComments = $paginator->paginate(
                $results,
                $request->query->getInt('page', 1),
                10
            );
            
            $forumComments = [];
        }

        return $this->render('back/comments.html.twig', [
            'current_tab' => $currentTab,
            'forumComments' => $forumComments,
            'contentComments' => $contentComments,
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    #[Route('/add', name: 'app_comment_add', methods: ['POST'])]
    public function add(
        Request $request,
        ForumPostRepository $forumPostRepository,
        ContentRepository $contentRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $type = $request->request->get('type');
        $targetId = $request->request->get('target_id');
        $content = $request->request->get('content');

        if ($type === 'forum') {
            $post = $forumPostRepository->find($targetId);
            if (!$post) {
                $this->addFlash('error', 'Forum post not found.');
                return $this->redirectToRoute('app_comments_back', ['tab' => 'forum']);
            }

            $reponse = new Reponse();
            $reponse->setContent($content);
            $reponse->setPost($post);
            $reponse->setAuthor($this->getUser());
            $entityManager->persist($reponse);
            $entityManager->flush();

            $this->addFlash('success', 'Comment added to forum post.');
            return $this->redirectToRoute('app_comments_back', ['tab' => 'forum']);
        } else {
            $guide = $contentRepository->find($targetId);
            if (!$guide) {
                $this->addFlash('error', 'Content not found.');
                return $this->redirectToRoute('app_comments_back', ['tab' => 'content']);
            }

            $comment = new Comment();
            $comment->setContent($content);
            $comment->setGuide($guide);
            $comment->setAuthor($this->getUser());
            $entityManager->persist($comment);
            $entityManager->flush();

            $this->addFlash('success', 'Comment added to content.');
            return $this->redirectToRoute('app_comments_back', ['tab' => 'content']);
        }
    }
}
