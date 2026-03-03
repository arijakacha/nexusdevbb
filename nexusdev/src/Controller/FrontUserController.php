<?php

namespace App\Controller;

use App\Repository\UserRepository;
use App\Repository\PlayerRepository;
use App\Repository\TeamInvitationRepository;
use App\Form\FrontUserProfileType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/FUser')]
final class FrontUserController extends AbstractController
{
    #[Route(name: 'front_user_index', methods: ['GET'])]
    public function index(UserRepository $userRepository): Response
    {
        return $this->render('front/user/index.html.twig', [
            'users' => $userRepository->findAll(),
        ]);
    }

    #[Route('/me', name: 'front_user_profile', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function profile(
        Request $request,
        EntityManagerInterface $entityManager,
        PlayerRepository $playerRepository,
        TeamInvitationRepository $invitationRepository
    ): Response {
        $user = $this->getUser();
        $form = $this->createForm(FrontUserProfileType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile|null $avatarFile */
            $avatarFile = $form->get('profilePicture')->getData();
            if ($avatarFile instanceof UploadedFile) {
                $uploadDir = $this->getParameter('logos_directory');
                $originalFilename = pathinfo($avatarFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = preg_replace('/[^A-Za-z0-9_\-]/', '_', $originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$avatarFile->guessExtension();

                $avatarFile->move($uploadDir, $newFilename);
                // Reuse logos_directory as base and store relative path
                $user->setProfilePicture($newFilename);

                // If this user has a linked player in session, sync avatar there too
                $session = $request->getSession();
                $playerId = $session->get('my_player_id');
                $linkedPlayer = null;
                if ($playerId) {
                    $linkedPlayer = $playerRepository->find($playerId);
                    if ($linkedPlayer) {
                        $linkedPlayer->setProfilePicture($newFilename);
                    }
                }

                // Fallback: if no session link, try to find the player's row by nickname = username
                if (!$linkedPlayer) {
                    $guessedPlayer = $playerRepository->findOneBy(['nickname' => $user->getUsername()]);
                    if ($guessedPlayer) {
                        $guessedPlayer->setProfilePicture($newFilename);
                        // restore session link for future requests
                        $session->set('my_player_id', $guessedPlayer->getId());
                    }
                }
            }

            $entityManager->flush();
            $this->addFlash('success', 'Profile updated successfully.');

            return $this->redirectToRoute('front_user_profile');
        }

        $session = $request->getSession();
        $player = null;
        $playerId = $session->get('my_player_id');
        if ($playerId) {
            $player = $playerRepository->find($playerId);
        }

        // Try to find player by user relationship (most reliable)
        if (!$player) {
            $player = $playerRepository->findByUser($user);
            if ($player) {
                $session->set('my_player_id', $player->getId());
            }
        }

        // If we lost the session link but the user is marked as having a player,
        // try to guess their player by nickname and restore my_player_id
        if (!$player && method_exists($user, 'hasPlayer') && $user->hasPlayer()) {
            $player = $playerRepository->findOneBy(['nickname' => $user->getUsername()]);
            if ($player) {
                $session->set('my_player_id', $player->getId());
            }
        }

        // Get pending team invitations for this player
        $pendingInvitations = [];
        if ($player) {
            $pendingInvitations = $invitationRepository->findPendingForPlayer($player);
        }

        return $this->render('front/user/profile.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
            'player' => $player,
            'pendingInvitations' => $pendingInvitations,
        ]);
    }
}
