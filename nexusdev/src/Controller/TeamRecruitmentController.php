<?php

namespace App\Controller;

use App\Entity\Notification;
use App\Entity\Player;
use App\Entity\Team;
use App\Entity\TeamInvitation;
use App\Repository\PlayerRepository;
use App\Repository\TeamInvitationRepository;
use App\Repository\TeamRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/team/recruitment')]
#[IsGranted('ROLE_USER')]
class TeamRecruitmentController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private TeamInvitationRepository $invitationRepository;
    private TeamRepository $teamRepository;
    private PlayerRepository $playerRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        TeamInvitationRepository $invitationRepository,
        TeamRepository $teamRepository,
        PlayerRepository $playerRepository
    ) {
        $this->entityManager = $entityManager;
        $this->invitationRepository = $invitationRepository;
        $this->teamRepository = $teamRepository;
        $this->playerRepository = $playerRepository;
    }

    #[Route('/browse-players', name: 'app_team_browse_players', methods: ['GET'])]
    public function browsePlayers(Request $request): Response
    {
        $user = $this->getUser();
        $player = $user?->getPlayer();

        if (!$player || !$player->getTeam()) {
            $this->addFlash('error', 'You must be in a team to recruit players.');
            return $this->redirectToRoute('app_player_dashboard', ['id' => $player?->getId() ?? 0]);
        }

        $team = $player->getTeam();
        $search = $request->query->get('search');
        $gameId = $request->query->get('game');

        $qb = $this->playerRepository->createQueryBuilder('p')
            ->leftJoin('p.team', 't')
            ->where('t.id IS NULL OR t.id != :teamId')
            ->setParameter('teamId', $team->getId());

        if ($search) {
            $qb->andWhere('p.nickname LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        if ($gameId) {
            $qb->andWhere('p.game = :gameId')
                ->setParameter('gameId', $gameId);
        }

        $availablePlayers = $qb->getQuery()->getResult();

        // Get pending invitations for this team
        $pendingInvitations = $this->invitationRepository->findBy(['team' => $team, 'status' => TeamInvitation::STATUS_PENDING]);
        $invitedPlayerIds = array_map(fn($inv) => $inv->getPlayer()->getId(), $pendingInvitations);

        return $this->render('team_recruitment/browse_players.html.twig', [
            'players' => $availablePlayers,
            'team' => $team,
            'invitedPlayerIds' => $invitedPlayerIds,
            'search' => $search,
        ]);
    }

    #[Route('/invite/{playerId}', name: 'app_team_invite_player', methods: ['POST'])]
    public function invitePlayer(int $playerId, Request $request): Response
    {
        $user = $this->getUser();
        $teamPlayer = $user?->getPlayer();

        if (!$teamPlayer || !$teamPlayer->getTeam()) {
            return $this->json(['success' => false, 'error' => 'You must be in a team to invite players.']);
        }

        $team = $teamPlayer->getTeam();
        $player = $this->playerRepository->find($playerId);

        if (!$player) {
            return $this->json(['success' => false, 'error' => 'Player not found.']);
        }

        if ($player->getTeam()) {
            return $this->json(['success' => false, 'error' => 'Player is already in a team.']);
        }

        // Check if already invited
        if ($this->invitationRepository->hasPendingInvitation($team, $player)) {
            return $this->json(['success' => false, 'error' => 'Player has already been invited to this team.']);
        }

        $message = $request->request->get('message', '');

        $invitation = new TeamInvitation();
        $invitation->setTeam($team);
        $invitation->setPlayer($player);
        $invitation->setMessage($message);
        $invitation->setStatus(TeamInvitation::STATUS_PENDING);

        $this->entityManager->persist($invitation);

        // Create notification for player
        $notification = new Notification();
        $notification->setUser($player->getUser());
        $notification->setMessage(sprintf(
            '🎮 Team **%s** wants to recruit you! Click to view and respond.',
            $team->getName()
        ));
        $notification->setIsRead(false);
        $notification->setCreatedAt(new \DateTime());

        $this->entityManager->persist($notification);
        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Invitation sent successfully!'
        ]);
    }

    #[Route('/my-invitations', name: 'app_player_invitations', methods: ['GET'])]
    public function myInvitations(): Response
    {
        $user = $this->getUser();
        $player = $user?->getPlayer();

        if (!$player) {
            $this->addFlash('error', 'You need a player profile to view invitations.');
            return $this->redirectToRoute('app_home');
        }

        $pendingInvitations = $this->invitationRepository->findPendingForPlayer($player);

        return $this->render('team_recruitment/my_invitations.html.twig', [
            'invitations' => $pendingInvitations,
            'player' => $player,
        ]);
    }

    #[Route('/respond/{invitationId}', name: 'app_team_invitation_respond', methods: ['POST'])]
    public function respondToInvitation(int $invitationId, Request $request): Response
    {
        $user = $this->getUser();
        $player = $user?->getPlayer();

        if (!$player) {
            return $this->json(['success' => false, 'error' => 'Player not found.']);
        }

        $invitation = $this->invitationRepository->find($invitationId);

        if (!$invitation || $invitation->getPlayer() !== $player) {
            return $this->json(['success' => false, 'error' => 'Invitation not found.']);
        }

        if (!$invitation->isPending()) {
            return $this->json(['success' => false, 'error' => 'This invitation has already been responded to.']);
        }

        $action = $request->request->get('action');
        
        // Get team reference for both accept and decline actions
        $team = $invitation->getTeam();

        if ($action === 'accept') {
            $player->setTeam($team);

            // Create notification for team
            $teamNotification = new Notification();
            $teamNotification->setMessage(sprintf(
                '✅ **%s** has joined your team **%s**!',
                $player->getNickname(),
                $team->getName()
            ));
            $teamNotification->setIsRead(false);
            $teamNotification->setCreatedAt(new \DateTimeImmutable());

            // Find team captain to notify
            $notified = false;
            foreach ($team->getPlayers() as $teamPlayer) {
                if ($teamPlayer->getUser() && $teamPlayer->getUser() !== $user) {
                    $teamNotification->setUser($teamPlayer->getUser());
                    $this->entityManager->persist($teamNotification);
                    $notified = true;
                    break;
                }
            }

            // If no team members to notify, skip the notification
            if (!$notified) {
                // No team members with user accounts to notify - skip notification
            }

            $this->entityManager->flush();

            // Create notification for organization owner
            $orgOwnerNotification = new Notification();
            $orgOwnerNotification->setMessage(sprintf(
                '✅ **%s** has accepted your invitation and joined team **%s**!',
                $player->getNickname(),
                $team->getName()
            ));
            $orgOwnerNotification->setIsRead(false);
            $orgOwnerNotification->setCreatedAt(new \DateTimeImmutable());

            // Find organization owner through team
            $organization = $team->getOrganization();
            if ($organization && $organization->getOwner()) {
                $orgOwnerNotification->setUser($organization->getOwner());
                $this->entityManager->persist($orgOwnerNotification);
            }

            $this->entityManager->flush();

            return $this->json([
                'success' => true,
                'message' => 'You have joined the team!',
                'redirect' => $this->generateUrl('app_player_dashboard', ['id' => $player->getId()])
            ]);
        } elseif ($action === 'decline') {
            $invitation->setStatus(TeamInvitation::STATUS_DECLINED);
            $invitation->setRespondedAt(new \DateTime());

            $this->entityManager->flush();

            // Create notification for organization owner
            $orgOwnerNotification = new Notification();
            $orgOwnerNotification->setMessage(sprintf(
                '❌ **%s** has declined your invitation to join team **%s**',
                $player->getNickname(),
                $team->getName()
            ));
            $orgOwnerNotification->setIsRead(false);
            $orgOwnerNotification->setCreatedAt(new \DateTimeImmutable());

            // Find organization owner through team
            $organization = $team->getOrganization();
            if ($organization && $organization->getOwner()) {
                $orgOwnerNotification->setUser($organization->getOwner());
                $this->entityManager->persist($orgOwnerNotification);
            }

            $this->entityManager->flush();

            return $this->json([
                'success' => true,
                'message' => 'Invitation declined.',
                'redirect' => $this->generateUrl('app_player_invitations')
            ]);
        }

        return $this->json(['success' => false, 'error' => 'Invalid action.']);
    }

    #[Route('/cancel/{invitationId}', name: 'app_team_cancel_invitation', methods: ['POST'])]
    public function cancelInvitation(int $invitationId): Response
    {
        $user = $this->getUser();
        $player = $user?->getPlayer();

        if (!$player || !$player->getTeam()) {
            return $this->json(['success' => false, 'error' => 'Unauthorized.']);
        }

        $invitation = $this->invitationRepository->find($invitationId);

        if (!$invitation || $invitation->getTeam() !== $player->getTeam()) {
            return $this->json(['success' => false, 'error' => 'Invitation not found.']);
        }

        if (!$invitation->isPending()) {
            return $this->json(['success' => false, 'error' => 'Can only cancel pending invitations.']);
        }

        $this->entityManager->remove($invitation);
        $this->entityManager->flush();

        return $this->json(['success' => true, 'message' => 'Invitation cancelled.']);
    }
}
