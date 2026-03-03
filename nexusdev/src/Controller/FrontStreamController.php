<?php

namespace App\Controller;

use App\Entity\Player;
use App\Entity\Stream;
use App\Repository\PlayerRepository;
use App\Repository\StreamRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/FStream')]
final class FrontStreamController extends AbstractController
{
    #[Route(name: 'front_stream_index', methods: ['GET'])]
    public function index(
        StreamRepository $streamRepository,
        PaginatorInterface $paginator,
        Request $request
    ): Response {
        $qb = $streamRepository->createQueryBuilder('s')
            ->leftJoin('s.player', 'p')
            ->addSelect('p');

        // Search filters
        $search = $request->query->get('search');
        $isLive = $request->query->get('isLive');
        $sortBy = $request->query->get('sortBy', 'createdAt');
        $sortOrder = $request->query->get('sortOrder', 'DESC');

        if ($search) {
            $qb->andWhere('s.title LIKE :search OR p.nickname LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        if ($isLive !== null && $isLive !== '') {
            $qb->andWhere('s.isLive = :isLive')
               ->setParameter('isLive', $isLive === '1' || $isLive === 'true');
        }

        $allowedSortFields = ['title', 'createdAt', 'viewerCount'];
        if (\in_array($sortBy, $allowedSortFields, true)) {
            $qb->orderBy('s.' . $sortBy, strtoupper($sortOrder) === 'DESC' ? 'DESC' : 'ASC');
        }

        $pagination = $paginator->paginate(
            $qb,
            $request->query->getInt('page', 1),
            12
        );

        return $this->render('front/stream/index.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    #[Route('/new/{id}', name: 'front_stream_new', methods: ['GET', 'POST'])]
    public function new(
        int $id,
        Request $request,
        PlayerRepository $playerRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $player = $playerRepository->find($id);
        if (!$player instanceof Player) {
            throw $this->createNotFoundException('Player not found');
        }

        if (!$this->getUser()) {
            $this->addFlash('error', 'You must be logged in to create a stream.');

            return $this->redirectToRoute('app_login');
        }

        // Ensure the current user owns this player profile
        $session = $request->getSession();
        if ($session->get('my_player_id') !== $player->getId()) {
            $this->addFlash('error', 'You can only create streams for your own player profile.');

            return $this->redirectToRoute('front_player_game', ['id' => $player->getId()]);
        }

        if (!$player->isPro()) {
            $this->addFlash('error', 'Only PRO players can create a stream.');

            return $this->redirectToRoute('front_player_game', ['id' => $player->getId()]);
        }

        $stream = new Stream();
        $stream->setPlayer($player);

        $formBuilder = $this->createFormBuilder($stream);
        $formBuilder
            ->add('title')
            ->add('url')
            ->add('isLive', CheckboxType::class, [
                'required' => false,
                'label' => 'Mark as live now',
            ]);

        $form = $formBuilder->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($stream);
            $entityManager->flush();

            $this->addFlash('success', 'Stream saved successfully.');

            return $this->redirectToRoute('front_player_game', ['id' => $player->getId()]);
        }

        return $this->render('front/stream/new.html.twig', [
            'player' => $player,
            'form' => $form,
        ]);
    }
}
