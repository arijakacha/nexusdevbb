<?php

namespace App\Controller;

use App\Entity\PasswordResetToken;
use App\Entity\User;
use App\Form\ForgotPasswordRequestType;
use App\Form\ResetPasswordType;
use App\Repository\PasswordResetTokenRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

final class ForgotPasswordController extends AbstractController
{
    public function __construct(
        private UserRepository $userRepository,
        private PasswordResetTokenRepository $resetTokenRepository,
        private EntityManagerInterface $entityManager,
        private MailerInterface $mailer,
        private TokenGeneratorInterface $tokenGenerator,
        private UrlGeneratorInterface $urlGenerator,
        private UserPasswordHasherInterface $passwordHasher,
        private SluggerInterface $slugger
    ) {}

    #[Route('/forgot-password', name: 'app_forgot_password_request')]
    public function request(Request $request): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_post_login_redirect');
        }

        $form = $this->createForm(ForgotPasswordRequestType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $email = $form->get('email')->getData();
            error_log('PASSWORD RESET: Form submitted with email: ' . $email);
            
            $user = $this->userRepository->findOneBy(['email' => $email]);
            error_log('PASSWORD RESET: User found: ' . ($user ? 'YES (ID: ' . $user->getId() . ')' : 'NO'));

            // Always show the same message to avoid user enumeration
            $this->addFlash('info', 'If an account with this email exists, a password reset link has been sent.');

            if ($user) {
                error_log('PASSWORD RESET: Processing user ' . $user->getEmail());
                
                // Delete any existing tokens for this user
                $this->resetTokenRepository->deleteAllForUser($user);
                error_log('PASSWORD RESET: Deleted existing tokens');

                // Generate a raw token (will be sent in email)
                $rawToken = $this->tokenGenerator->generateToken();
                $tokenHash = hash('sha256', $rawToken);
                error_log('PASSWORD RESET: Generated token: ' . $rawToken);

                // Token valid for 30 minutes
                $expiresAt = new \DateTimeImmutable('+30 minutes');

                $resetToken = new PasswordResetToken($user, $tokenHash, $expiresAt);
                $this->resetTokenRepository->save($resetToken, true);
                error_log('PASSWORD RESET: Token saved to database');

                // Send email
                $resetUrl = $this->urlGenerator->generate('app_reset_password', ['token' => $rawToken], UrlGeneratorInterface::ABSOLUTE_URL);
                error_log('PASSWORD RESET: Reset URL generated: ' . $resetUrl);

                $email = (new Email())
                    ->from('stwstw599@gmail.com')
                    ->to($user->getEmail())
                    ->subject('Reset your NexusPlay password')
                    ->html($this->renderView('emails/reset_password.html.twig', [
                        'user' => $user,
                        'resetUrl' => $resetUrl,
                        'expiresAt' => $expiresAt,
                    ]));

                error_log('PASSWORD RESET: About to send email to ' . $user->getEmail());
                try {
                    // Send email immediately (not through messenger)
                    $this->mailer->send($email);
                    // Log successful email sending
                    error_log('PASSWORD RESET: ✅ Email sent successfully to: ' . $user->getEmail());
                } catch (\Exception $e) {
                    // Log the error for debugging
                    error_log('PASSWORD RESET: ❌ Failed to send password reset email: ' . $e->getMessage());
                    // In production, you should log this error
                    // For now, we silently fail to avoid leaking information
                }
            } else {
                error_log('PASSWORD RESET: User not found, no email sent');
            }

            error_log('PASSWORD RESET: Redirecting back to forgot password page');
            return $this->redirectToRoute('app_forgot_password_request');
        }

        return $this->render('security/forgot_password_request.html.twig', [
            'requestForm' => $form->createView(),
        ]);
    }

    #[Route('/reset-password/{token}', name: 'app_reset_password')]
    public function reset(string $token, Request $request): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_post_login_redirect');
        }

        $tokenHash = hash('sha256', $token);
        $resetToken = $this->resetTokenRepository->findOneByValidToken($tokenHash);

        if (!$resetToken) {
            $this->addFlash('danger', 'Invalid or expired reset link. Please try again.');
            return $this->redirectToRoute('app_forgot_password_request');
        }

        $form = $this->createForm(ResetPasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $resetToken->getUser();

            // Hash the new password
            $newPassword = $form->get('plainPassword')->getData();
            $hashedPassword = $this->passwordHasher->hashPassword($user, $newPassword);
            $user->setPassword($hashedPassword);

            // Mark token as used
            $resetToken->markAsUsed();
            $this->entityManager->flush();

            $this->addFlash('success', 'Your password has been reset successfully. You can now log in.');

            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/reset_password.html.twig', [
            'resetForm' => $form->createView(),
        ]);
    }
}
