<?php

namespace App\Controller;

use App\Entity\Notification;
use App\Form\NotificationType;
use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/BNotification')]
final class NotificationController extends AbstractController
{
    #[Route(name: 'app_notification_back', methods: ['GET', 'POST'])]
    public function back(
        Request $request,
        NotificationRepository $notificationRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $notifications = $notificationRepository->findAll();

        $notificationId = $request->query->getInt('id', 0);
        if ($notificationId > 0) {
            $notification = $notificationRepository->find($notificationId);
            if (!$notification) {
                throw $this->createNotFoundException('Notification not found');
            }
        } else {
            $notification = new Notification();
        }

        $form = $this->createForm(NotificationType::class, $notification);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $isNew = $notification->getId() === null;
            if ($isNew) {
                $entityManager->persist($notification);
            }
            $entityManager->flush();

            $this->addFlash('success', $isNew ? 'Notification created successfully.' : 'Notification updated successfully.');

            return $this->redirectToRoute('app_notification_back', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('notification/back.html.twig', [
            'notifications' => $notifications,
            'form' => $form,
            'editing' => $notification->getId() !== null,
            'currentNotification' => $notification,
        ]);
    }

    #[Route('/my', name: 'app_notifications', methods: ['GET'])]
    public function myNotifications(): Response
    {
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('You must be logged in.');
        }

        $notifications = $user->getNotifications();

        return $this->render('notification/my_notifications.html.twig', [
            'notifications' => $notifications,
        ]);
    }

    #[Route('/back/my', name: 'app_notifications_back_office', methods: ['GET'])]
    public function myNotificationsBackOffice(): Response
    {
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('You must be logged in.');
        }

        $notifications = $user->getNotifications();

        return $this->render('notification/my_notifications_back.html.twig', [
            'notifications' => $notifications,
        ]);
    }

    #[Route('/back/mark-all-read', name: 'app_notifications_mark_all_read_back', methods: ['GET'])]
    public function markAllReadBack(EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('You must be logged in.');
        }

        foreach ($user->getNotifications() as $notification) {
            if (!$notification->isRead()) {
                $notification->setIsRead(true);
            }
        }
        $entityManager->flush();

        $this->addFlash('success', 'All notifications marked as read.');
        return $this->redirectToRoute('app_notifications_back_office');
    }

    #[Route('/back/delete-all', name: 'app_notifications_delete_all_back', methods: ['GET'])]
    public function deleteAllBack(EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('You must be logged in.');
        }

        foreach ($user->getNotifications() as $notification) {
            $entityManager->remove($notification);
        }
        $entityManager->flush();

        $this->addFlash('success', 'All notifications deleted.');
        return $this->redirectToRoute('app_notifications_back_office');
    }

    #[Route('/{id}/read', name: 'app_notification_read', methods: ['POST'])]
    public function markAsRead(Notification $notification, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user || $notification->getUser() !== $user) {
            throw $this->createAccessDeniedException('Access denied.');
        }

        $notification->setIsRead(true);
        $entityManager->flush();

        if ($this->isGranted('ROLE_ORGANIZATION')) {
            return $this->redirectToRoute('app_organization_back', ['view' => 'notifications']);
        }

        return $this->redirectToRoute('app_notifications');
    }

    #[Route('/back/{id}/read', name: 'app_notification_read_back', methods: ['POST'])]
    public function markAsReadBack(Notification $notification, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user || $notification->getUser() !== $user) {
            throw $this->createAccessDeniedException('Access denied.');
        }

        $notification->setIsRead(true);
        $entityManager->flush();

        return $this->redirectToRoute('app_notifications_back_office');
    }

    #[Route('/back/{id}/delete', name: 'app_notification_delete_back', methods: ['POST'])]
    public function deleteBack(Notification $notification, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user || $notification->getUser() !== $user) {
            throw $this->createAccessDeniedException('Access denied.');
        }

        $entityManager->remove($notification);
        $entityManager->flush();

        $this->addFlash('success', 'Notification deleted successfully.');

        return $this->redirectToRoute('app_notifications_back_office', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/delete', name: 'app_notification_delete', methods: ['POST'])]
    public function delete(Notification $notification, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user || $notification->getUser() !== $user) {
            throw $this->createAccessDeniedException('Access denied.');
        }

        $entityManager->remove($notification);
        $entityManager->flush();

        $this->addFlash('success', 'Notification deleted successfully.');

        if ($this->isGranted('ROLE_ORGANIZATION')) {
            return $this->redirectToRoute('app_organization_back', ['view' => 'notifications'], Response::HTTP_SEE_OTHER);
        }

        return $this->redirectToRoute('app_notifications', [], Response::HTTP_SEE_OTHER);
    }
}
