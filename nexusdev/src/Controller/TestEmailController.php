<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;

final class TestEmailController extends AbstractController
{
    #[Route('/test-email', name: 'app_test_email')]
    public function sendTest(MailerInterface $mailer): Response
    {
        $email = (new Email())
            ->from($_ENV['MAILER_FROM'] ?? 'noreply@nexusplay.gg')
            ->to('akaichiiyed10@gmail.com')
            ->subject('Test email from NexusPlay')
            ->html('<p>If you receive this, email sending works.</p>');

        $mailer->send($email);

        return new Response('Test email sent to akaichiiyed10@gmail.com');
    }
}