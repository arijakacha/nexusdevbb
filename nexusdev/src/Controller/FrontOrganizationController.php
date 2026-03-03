<?php

namespace App\Controller;

use App\Repository\OrganizationRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/FOrganization')]
final class FrontOrganizationController extends AbstractController
{
    #[Route(name: 'front_organization_index', methods: ['GET'])]
    public function index(
        OrganizationRepository $organizationRepository,
        PaginatorInterface $paginator,
        Request $request
    ): Response {
        $qb = $organizationRepository->createQueryBuilder('o')
            ->leftJoin('o.owner', 'u')
            ->addSelect('u')
            ->where('o.deletedAt IS NULL');

        // Search filters
        $search = $request->query->get('search');
        $verified = $request->query->get('verified');
        $sortBy = $request->query->get('sortBy', 'name');
        $sortOrder = $request->query->get('sortOrder', 'ASC');

        if ($search) {
            $qb->andWhere('o.name LIKE :search OR o.description LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        if ($verified !== null && $verified !== '') {
            $qb->andWhere('o.isValidated = :verified')
               ->setParameter('verified', $verified === '1' || $verified === 'true');
        }

        $allowedSortFields = ['name', 'createdAt'];
        if (\in_array($sortBy, $allowedSortFields, true)) {
            $qb->orderBy('o.' . $sortBy, strtoupper($sortOrder) === 'DESC' ? 'DESC' : 'ASC');
        }

        $pagination = $paginator->paginate(
            $qb,
            $request->query->getInt('page', 1),
            12
        );

        return $this->render('front/organization/index.html.twig', [
            'pagination' => $pagination,
            'orgTypes' => ['Team', 'Tournament', 'Streaming', 'Community', 'Sponsor'],
        ]);
    }
}
