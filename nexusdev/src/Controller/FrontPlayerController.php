<?php

namespace App\Controller;

use App\Entity\Player;
use App\Repository\GameRepository;
use App\Repository\PlayerRepository;
use App\Repository\TeamRepository;
use App\Service\PdfGeneratorService;
use App\Service\QrCodeService;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/FPlayer')]
final class FrontPlayerController extends AbstractController
{
    #[Route(name: 'front_player_index', methods: ['GET'])]
    public function index(
        PlayerRepository $playerRepository,
        GameRepository $gameRepository,
        TeamRepository $teamRepository,
        PaginatorInterface $paginator,
        Request $request
    ): Response {
        $qb = $playerRepository->createQueryBuilder('p')
            ->leftJoin('p.game', 'g')
            ->leftJoin('p.team', 't')
            ->addSelect('g', 't');

        // Multi-criteria search
        $search = $request->query->get('search');
        $gameId = $request->query->getInt('game');
        $teamId = $request->query->getInt('team');
        $role = $request->query->get('role');
        $minScore = $request->query->getInt('minScore');
        $maxScore = $request->query->getInt('maxScore');
        $isPro = $request->query->get('isPro');
        $sortBy = $request->query->get('sortBy', 'score');
        $sortOrder = $request->query->get('sortOrder', 'DESC');

        if ($search) {
            $qb->andWhere('p.nickname LIKE :search OR p.realName LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        if ($gameId) {
            $qb->andWhere('p.game = :gameId')
               ->setParameter('gameId', $gameId);
        }

        if ($teamId) {
            $qb->andWhere('p.team = :teamId')
               ->setParameter('teamId', $teamId);
        }

        if ($role) {
            $qb->andWhere('p.role = :role')
               ->setParameter('role', $role);
        }

        if ($minScore) {
            $qb->andWhere('p.score >= :minScore')
               ->setParameter('minScore', $minScore);
        }

        if ($maxScore) {
            $qb->andWhere('p.score <= :maxScore')
               ->setParameter('maxScore', $maxScore);
        }

        if ($isPro !== null && $isPro !== '') {
            $qb->andWhere('p.isPro = :isPro')
               ->setParameter('isPro', $isPro === '1' || $isPro === 'true');
        }

        // Sorting
        $allowedSortFields = ['score', 'nickname', 'createdAt'];
        $allowedSortOrders = ['ASC', 'DESC'];
        
        if (\in_array($sortBy, $allowedSortFields, true) && \in_array(strtoupper($sortOrder), $allowedSortOrders, true)) {
            $qb->orderBy('p.' . $sortBy, strtoupper($sortOrder));
        } else {
            $qb->orderBy('p.score', 'DESC');
        }

        $pagination = $paginator->paginate(
            $qb,
            $request->query->getInt('page', 1),
            12
        );

        return $this->render('front/player/index.html.twig', [
            'pagination' => $pagination,
            'games' => $gameRepository->findAll(),
            'teams' => $teamRepository->findAll(),
            'roles' => ['Carry', 'Support', 'Mid', 'Offlane', 'Jungler', 'AWPer', 'IGL', 'Entry'],
        ]);
    }

    #[Route('/{id}/pdf', name: 'front_player_pdf', methods: ['GET'])]
    public function generatePdf(
        Player $player,
        PdfGeneratorService $pdfGenerator
    ): Response {
        $statistics = $player->getStatistics()->first() ?: null;
        $recentMatches = [];

        $pdfContent = $pdfGenerator->generatePlayerStatsPdf($player, $statistics, $recentMatches);

        return new Response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $player->getNickname() . '_stats.pdf"',
        ]);
    }

    #[Route('/{id}/qrcode', name: 'front_player_qrcode', methods: ['GET'])]
    public function generateQrCode(
        Player $player,
        QrCodeService $qrCodeService
    ): Response {
        $qrCode = $qrCodeService->generatePlayerQrCode($player->getId(), $player->getNickname());

        return $this->render('player/qrcode.html.twig', [
            'player' => $player,
            'qrCode' => $qrCode,
        ]);
    }
}
