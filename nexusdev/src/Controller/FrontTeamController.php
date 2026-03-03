<?php

namespace App\Controller;

use App\Entity\Team;
use App\Repository\GameRepository;
use App\Repository\TeamRepository;
use App\Service\PdfGeneratorService;
use App\Service\QrCodeService;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/FTeam')]
final class FrontTeamController extends AbstractController
{
    #[Route(name: 'front_team_index', methods: ['GET'])]
    public function index(
        TeamRepository $teamRepository,
        GameRepository $gameRepository,
        PaginatorInterface $paginator,
        Request $request
    ): Response {
        $qb = $teamRepository->createQueryBuilder('t')
            ->leftJoin('t.game', 'g')
            ->addSelect('g');

        // Search filter
        $search = $request->query->get('search');
        $gameId = $request->query->getInt('game');
        $country = $request->query->get('country');
        $minYear = $request->query->getInt('minYear');
        $sortBy = $request->query->get('sortBy', 'name');
        $sortOrder = $request->query->get('sortOrder', 'ASC');

        if ($search) {
            $qb->andWhere('t.name LIKE :search OR t.description LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        if ($gameId) {
            $qb->andWhere('t.game = :gameId')
               ->setParameter('gameId', $gameId);
        }

        if ($country) {
            $qb->andWhere('t.country = :country')
               ->setParameter('country', $country);
        }

        if ($minYear) {
            $qb->andWhere('t.foundationYear >= :minYear')
               ->setParameter('minYear', $minYear);
        }

        $allowedSortFields = ['name', 'foundationYear', 'createdAt'];
        if (\in_array($sortBy, $allowedSortFields, true)) {
            $qb->orderBy('t.' . $sortBy, strtoupper($sortOrder) === 'DESC' ? 'DESC' : 'ASC');
        }

        $pagination = $paginator->paginate(
            $qb,
            $request->query->getInt('page', 1),
            12
        );

        return $this->render('front/team/index.html.twig', [
            'pagination' => $pagination,
            'games' => $gameRepository->findAll(),
            'countries' => $teamRepository->findDistinctCountries(),
        ]);
    }

    #[Route('/{id}/pdf', name: 'front_team_pdf', methods: ['GET'])]
    public function generatePdf(Team $team, PdfGeneratorService $pdfGenerator): Response
    {
        $stats = [
            'totalMatches' => 0,
            'totalWins' => 0,
            'winRate' => 0,
            'kdRatio' => 0,
        ];
        
        $pdfContent = $pdfGenerator->generateTeamReportPdf($team, $stats, $team->getPlayers()->toArray());

        return new Response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $team->getName() . '_report.pdf"',
        ]);
    }

    #[Route('/{id}/qrcode', name: 'front_team_qrcode', methods: ['GET'])]
    public function generateQrCode(Team $team, QrCodeService $qrCodeService): Response
    {
        $qrCode = $qrCodeService->generateTeamQrCode($team->getId(), $team->getName());

        return $this->render('team/qrcode.html.twig', [
            'team' => $team,
            'qrCode' => $qrCode,
        ]);
    }
}
