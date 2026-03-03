<?php

namespace App\Controller;

use App\Entity\Player;
use App\Form\PlayerType;
use App\Repository\OrganizationRepository;
use App\Repository\PlayerRepository;
use App\Repository\TeamRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Security;

#[Route('/BPlayer')]
final class PlayerController extends AbstractController
{
    #[Route(name: 'app_player_back', methods: ['GET', 'POST'])]
    public function back(
        Request $request,
        PlayerRepository $playerRepository,
        EntityManagerInterface $entityManager,
        PaginatorInterface $paginator
    ): Response {
        $qb = $playerRepository->createQueryBuilder('p')
            ->leftJoin('p.user', 'u')
            ->addSelect('u');

        $search = $request->query->get('search');
        if ($search) {
            $qb->andWhere('p.nickname LIKE :search OR u.username LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        $pagination = $paginator->paginate(
            $qb,
            $request->query->getInt('page', 1),
            10
        );

        $canManagePlayers = $this->isGranted('ROLE_ADMIN');

        $player = null;
        $form = null;
        $editing = false;

        if ($canManagePlayers) {
            $playerId = $request->query->getInt('id', 0);
            if ($playerId > 0) {
                $player = $playerRepository->find($playerId);
                if (!$player) {
                    throw $this->createNotFoundException('Player not found');
                }
            } else {
                $player = new Player();
            }

            $form = $this->createForm(PlayerType::class, $player);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $isNew = $player->getId() === null;
                
                // Handle profile picture file upload
                $profilePictureFile = $form->get('profilePictureFile')->getData();
                if ($profilePictureFile) {
                    $uploadsDir = $this->getParameter('kernel.project_dir').'/public/uploads/players';
                    
                    if (!is_dir($uploadsDir)) {
                        mkdir($uploadsDir, 0775, true);
                    }
                    
                    $originalFilename = pathinfo($profilePictureFile->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = preg_replace('/[^a-zA-Z0-9_-]/', '-', $originalFilename);
                    $newFilename = $safeFilename.'-'.uniqid().'.'.$profilePictureFile->guessExtension();
                    
                    $profilePictureFile->move($uploadsDir, $newFilename);
                    $player->setProfilePicture('/uploads/players/'.$newFilename);
                }
                
                if ($isNew) {
                    $entityManager->persist($player);
                }
                $entityManager->flush();

                $this->addFlash('success', $isNew ? 'Player created successfully.' : 'Player updated successfully.');

                return $this->redirectToRoute('app_player_back', [], Response::HTTP_SEE_OTHER);
            }

            $editing = $player->getId() !== null;
        }

        $template = $canManagePlayers ? 'player/back.html.twig' : 'player/back_org.html.twig';

        return $this->render($template, [
            'pagination' => $pagination,
            'form' => $form,
            'editing' => $editing,
            'currentPlayer' => $player,
            'can_manage_players' => $canManagePlayers,
        ]);
    }

    #[Route('/recruit', name: 'app_player_recruit', methods: ['GET', 'POST'])]
    public function recruit(
        Request $request,
        PlayerRepository $playerRepository,
        TeamRepository $teamRepository,
        OrganizationRepository $organizationRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('You must be logged in to recruit players.');
        }

        $organization = $organizationRepository->findOneBy(['owner' => $user]);
        if (!$organization) {
            $this->addFlash('error', 'You must create your organization first.');

            return $this->redirectToRoute('app_organization_back');
        }

        $proPlayers = $playerRepository->findBy(['isPro' => true]);
        $teams = $teamRepository->findBy(['organization' => $organization]);

        $formBuilder = $this->createFormBuilder();
        $formBuilder
            ->add('player', EntityType::class, [
                'class' => Player::class,
                'choices' => $proPlayers,
                'choice_label' => 'nickname',
            ])
            ->add('team', EntityType::class, [
                'class' => \App\Entity\Team::class,
                'choices' => $teams,
                'choice_label' => 'name',
            ]);

        $form = $formBuilder->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            /** @var Player $player */
            $player = $data['player'];
            /** @var \App\Entity\Team $team */
            $team = $data['team'];

            $player->setTeam($team);
            $entityManager->flush();

            $this->addFlash('success', sprintf('Player %s has been assigned to team %s.', $player->getNickname(), $team->getName()));

            return $this->redirectToRoute('app_player_recruit');
        }

        return $this->render('player/recruit.html.twig', [
            'organization' => $organization,
            'proPlayers' => $proPlayers,
            'teams' => $teams,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_player_delete', methods: ['POST'])]
    public function delete(Request $request, Player $player, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Only admins can delete players.');
        }

        if ($this->isCsrfTokenValid('delete'.$player->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($player);
            $entityManager->flush();
            $this->addFlash('success', 'Player deleted successfully.');
        }

        return $this->redirectToRoute('app_player_back', [], Response::HTTP_SEE_OTHER);
    }
}
