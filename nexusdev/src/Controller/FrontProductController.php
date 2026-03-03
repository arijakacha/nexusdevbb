<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/FProduct')]
final class FrontProductController extends AbstractController
{
    #[Route(name: 'front_product_index', methods: ['GET'])]
    public function index(
        ProductRepository $productRepository,
        PaginatorInterface $paginator,
        Request $request
    ): Response {
        $qb = $productRepository->createQueryBuilder('p')
            ->where('p.deletedAt IS NULL');

        // Search filters
        $search = $request->query->get('search');
        $type = $request->query->get('type');
        $minPrice = $request->query->get('minPrice');
        $maxPrice = $request->query->get('maxPrice');
        $inStock = $request->query->get('inStock');
        $sortBy = $request->query->get('sortBy', 'name');
        $sortOrder = $request->query->get('sortOrder', 'DESC');

        if ($search) {
            $qb->andWhere('p.name LIKE :search OR p.description LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        if ($type) {
            $qb->andWhere('p.type = :type')
               ->setParameter('type', $type);
        }

        if ($minPrice) {
            $qb->andWhere('p.price >= :minPrice')
               ->setParameter('minPrice', $minPrice);
        }

        if ($maxPrice) {
            $qb->andWhere('p.price <= :maxPrice')
               ->setParameter('maxPrice', $maxPrice);
        }

        if ($inStock !== null && $inStock !== '') {
            $qb->andWhere('p.quantity > 0');
        }

        $allowedSortFields = ['name', 'price', 'quantity'];
        if (\in_array($sortBy, $allowedSortFields, true)) {
            $qb->orderBy('p.' . $sortBy, strtoupper($sortOrder) === 'DESC' ? 'DESC' : 'ASC');
        }

        $pagination = $paginator->paginate(
            $qb,
            $request->query->getInt('page', 1),
            12
        );

        return $this->render('front/product/index.html.twig', [
            'pagination' => $pagination,
            'types' => $productRepository->findDistinctTypes(),
        ]);
    }
}
