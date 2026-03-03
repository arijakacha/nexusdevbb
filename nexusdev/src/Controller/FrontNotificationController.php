<?php

namespace App\Controller;

use App\Repository\NotificationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/FNotification')]
final class FrontNotificationController extends AbstractController
{
    #[Route(name: 'front_notification_index', methods: ['GET'])]
    public function index(NotificationRepository $notificationRepository): Response
    {
        return $this->render('front/notification/index.html.twig', [
            'notifications' => $notificationRepository->findAll(),
        ]);
    }
}
