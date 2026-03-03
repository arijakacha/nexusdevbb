<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\ForumPost;
use App\Entity\Report;
use App\Entity\User;
use App\Repository\CommentRepository;
use App\Repository\ForumPostRepository;
use App\Repository\ReportRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/report')]
#[IsGranted('ROLE_USER')]
class ReportController extends AbstractController
{
    #[Route('/post/{postId}', name: 'app_report_post', methods: ['POST'])]
    public function reportPost(
        int $postId,
        Request $request,
        ForumPostRepository $postRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $user = $this->getUser();
        $post = $postRepository->find($postId);

        if (!$post) {
            $this->addFlash('error', 'Post not found.');
            return $this->redirectToRoute('front_forum_post_index');
        }

        $type = $request->request->get('type', 'inappropriate');
        $description = $request->request->get('description');

        $report = new Report();
        $report->setReporter($user);
        $report->setPost($post);
        $report->setReportedUser($post->getAuthor());
        $report->setType($type);
        $report->setDescription($description);

        $entityManager->persist($report);
        $entityManager->flush();

        $this->addFlash('success', 'Thank you for your report. We will review it shortly.');
        return $this->redirectToRoute('front_forum_post_index');
    }

    #[Route('/comment/{commentId}', name: 'app_report_comment', methods: ['POST'])]
    public function reportComment(
        int $commentId,
        Request $request,
        CommentRepository $commentRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $user = $this->getUser();
        $comment = $commentRepository->find($commentId);

        if (!$comment) {
            $this->addFlash('error', 'Comment not found.');
            return $this->redirectToRoute('front_forum_post_index');
        }

        $type = $request->request->get('type', 'inappropriate');
        $description = $request->request->get('description');

        $report = new Report();
        $report->setReporter($user);
        $report->setComment($comment);
        $report->setReportedUser($comment->getAuthor());
        $report->setType($type);
        $report->setDescription($description);

        $entityManager->persist($report);
        $entityManager->flush();

        $this->addFlash('success', 'Thank you for your report. We will review it shortly.');
        return $this->redirectToRoute('front_forum_post_index');
    }

    #[Route('/user/{userId}', name: 'app_report_user', methods: ['POST'])]
    public function reportUser(
        int $userId,
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $currentUser = $this->getUser();
        $reportedUser = $userRepository->find($userId);

        if (!$reportedUser) {
            $this->addFlash('error', 'User not found.');
            return $this->redirectToRoute('front_forum_post_index');
        }

        if ($reportedUser === $currentUser) {
            $this->addFlash('error', 'You cannot report yourself.');
            return $this->redirectToRoute('front_forum_post_index');
        }

        $type = $request->request->get('type', 'inappropriate');
        $description = $request->request->get('description');

        $report = new Report();
        $report->setReporter($currentUser);
        $report->setReportedUser($reportedUser);
        $report->setType($type);
        $report->setDescription($description);

        $entityManager->persist($report);
        $entityManager->flush();

        $this->addFlash('success', 'Thank you for your report. We will review it shortly.');
        return $this->redirectToRoute('front_forum_post_index');
    }

    #[Route('/admin/list', name: 'app_report_admin_list', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function adminList(ReportRepository $reportRepository): Response
    {
        $pendingReports = $reportRepository->findPendingReports();
        $totalPending = $reportRepository->countPendingReports();

        return $this->render('report/admin_list.html.twig', [
            'reports' => $pendingReports,
            'totalPending' => $totalPending,
        ]);
    }

    #[Route('/admin/resolve/{id}', name: 'app_report_resolve', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function resolveReport(
        int $id,
        ReportRepository $reportRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $report = $reportRepository->find($id);

        if (!$report) {
            $this->addFlash('error', 'Report not found.');
            return $this->redirectToRoute('app_report_admin_list');
        }

        $report->setStatus(Report::STATUS_RESOLVED);
        $report->setResolvedAt(new \DateTimeImmutable());
        $report->setResolvedBy($this->getUser());

        $entityManager->flush();

        $this->addFlash('success', 'Report resolved successfully.');
        return $this->redirectToRoute('app_report_admin_list');
    }

    #[Route('/admin/dismiss/{id}', name: 'app_report_dismiss', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function dismissReport(
        int $id,
        ReportRepository $reportRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $report = $reportRepository->find($id);

        if (!$report) {
            $this->addFlash('error', 'Report not found.');
            return $this->redirectToRoute('app_report_admin_list');
        }

        $report->setStatus(Report::STATUS_DISMISSED);
        $report->setResolvedAt(new \DateTimeImmutable());
        $report->setResolvedBy($this->getUser());

        $entityManager->flush();

        $this->addFlash('success', 'Report dismissed successfully.');
        return $this->redirectToRoute('app_report_admin_list');
    }
}
