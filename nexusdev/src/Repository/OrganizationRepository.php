<?php

namespace App\Repository;

use App\Entity\Organization;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Organization>
 */
class OrganizationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Organization::class);
    }

    /**
     * Find organizations that have teams for a specific game
     */
    public function findWithTeamsByGame(int $gameId): array
    {
        return $this->createQueryBuilder('o')
            ->select('o', 't')
            ->leftJoin('o.teams', 't')
            ->where('t.game = :gameId')
            ->setParameter('gameId', $gameId)
            ->getQuery()
            ->getResult();
    }
}
