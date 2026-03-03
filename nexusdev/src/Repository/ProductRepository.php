<?php

namespace App\Repository;

use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Product>
 */
class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    /**
     * Find distinct types for filter dropdown
     * @return array<string>
     */
    public function findDistinctTypes(): array
    {
        $result = $this->createQueryBuilder('p')
            ->select('DISTINCT p.type')
            ->where('p.type IS NOT NULL')
            ->orderBy('p.type', 'ASC')
            ->getQuery()
            ->getSingleColumnResult();

        return $result;
    }
}
