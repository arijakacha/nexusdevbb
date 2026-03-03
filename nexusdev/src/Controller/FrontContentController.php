<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Content;
use App\Entity\ForumPost;
use App\Repository\ContentRepository;
use App\Repository\ForumPostRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use FOS\CKEditorBundle\Form\Type\CKEditorType;

#[Route('/FContent')]
final class FrontContentController extends AbstractController
{
    #[Route(name: 'front_content_index', methods: ['GET'])]
    public function index(
        ContentRepository $contentRepository,
        PaginatorInterface $paginator,
        Request $request
    ): Response {
        $qb = $contentRepository->createQueryBuilder('c')
            ->where('c.deletedAt IS NULL');

        // Search filters
        $search = $request->query->get('search');
        $type = $request->query->get('type');
        $author = $request->query->get('author');
        $sortBy = $request->query->get('sortBy', 'createdAt');
        $sortOrder = $request->query->get('sortOrder', 'DESC');

        if ($search) {
            $qb->andWhere('c.title LIKE :search OR c.body LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        if ($type) {
            $qb->andWhere('c.type = :type')
               ->setParameter('type', $type);
        }

        if ($author) {
            $qb->andWhere('c.author = :author')
               ->setParameter('author', $author);
        }

        $allowedSortFields = ['title', 'createdAt', 'updatedAt'];
        if (\in_array($sortBy, $allowedSortFields, true)) {
            $qb->orderBy('c.' . $sortBy, strtoupper($sortOrder) === 'DESC' ? 'DESC' : 'ASC');
        }

        $pagination = $paginator->paginate(
            $qb,
            $request->query->getInt('page', 1),
            12
        );

        return $this->render('front/content/index.html.twig', [
            'pagination' => $pagination,
            'contentTypes' => ['Guide', 'News', 'Tutorial', 'Review'],
        ]);
    }

    #[Route('/{id}', name: 'front_content_show', methods: ['GET', 'POST'])]
    public function show(
        Content $content,
        ContentRepository $contentRepository,
        ForumPostRepository $forumPostRepository,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        if (method_exists($content, 'getDeletedAt') && $content->getDeletedAt() !== null) {
            throw $this->createNotFoundException('Content not found');
        }

        $popularContents = $contentRepository->findBy(['deletedAt' => null], ['createdAt' => 'DESC'], 5);

        // Back guide comments with a dedicated forum thread identified by a special title
        $threadTitle = sprintf('[GUIDE #%d] %s', $content->getId(), $content->getTitle());
        $thread = $forumPostRepository->findOneBy(['title' => $threadTitle]);
        $comments = $thread ? $thread->getComments() : [];
        $commentFormView = null;

        $user = $this->getUser();
        if ($user) {
            $formBuilder = $this->createFormBuilder();
            $formBuilder->add('content', CKEditorType::class, [
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
                    'id' => 'guide_comment_content',
                    'rows' => 4,
                    'class' => 'form-control',
                    'placeholder' => 'Share your thoughts on this guide...'
                ],
            ]);

            $form = $formBuilder->getForm();
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                // Lazily create the forum thread for this guide if it doesn't exist yet
                if (!$thread) {
                    $thread = new ForumPost();
                    $thread->setTitle($threadTitle);
                    $thread->setContent(sprintf('Discussion thread for guide #%d', $content->getId()));
                    $thread->setAuthor($user);
                    $entityManager->persist($thread);
                }

                $data = $form->getData();
                $comment = new Comment();
                $comment->setAuthor($user);
                $comment->setPost($thread);
                $comment->setContent($data['content']);

                $entityManager->persist($comment);
                $entityManager->flush();

                $this->addFlash('success', 'Comment added to this guide.');

                return $this->redirectToRoute('front_content_show', ['id' => $content->getId()]);
            }

            $commentFormView = $form->createView();

            // Refresh comments from persisted thread
            if ($thread) {
                $comments = $thread->getComments();
            }
        }

        return $this->render('front/content/show.html.twig', [
            'content' => $content,
            'popularContents' => $popularContents,
            'comments' => $comments,
            'commentForm' => $commentFormView,
        ]);
    }
}
