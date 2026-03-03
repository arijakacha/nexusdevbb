<?php

namespace App\Repository;

use App\Entity\Organization;
use App\Entity\Team;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Team>
 */
class TeamRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Team::class);
    }

    /**
     * Get teams by organization with player count
     * @return array<Team>
     */
    public function findByOrganizationWithStats(Organization $organization): array
    {
        return $this->createQueryBuilder('t')
            ->leftJoin('t.players', 'p')
            ->addSelect('COUNT(p.id) as playerCount')
            ->andWhere('t.organization = :organization')
            ->setParameter('organization', $organization)
            ->groupBy('t.id')
            ->getQuery()
            ->getResult();
    }

    /**
     * Calculate total matches played by all teams in an organization
     */
    public function getTotalMatchesForOrganization(Organization $organization): int
    {
        $result = $this->createQueryBuilder('t')
            ->select('COALESCE(SUM(s.matchesPlayed), 0)')
            ->leftJoin('t.players', 'p')
            ->leftJoin('p.statistics', 's')
            ->andWhere('t.organization = :organization')
            ->setParameter('organization', $organization)
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $result;
    }

    /**
     * Calculate total wins for an organization
     */
    public function getTotalWinsForOrganization(Organization $organization): int
    {
        $result = $this->createQueryBuilder('t')
            ->select('COALESCE(SUM(s.wins), 0)')
            ->leftJoin('t.players', 'p')
            ->leftJoin('p.statistics', 's')
            ->andWhere('t.organization = :organization')
            ->setParameter('organization', $organization)
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $result;
    }

    /**
     * Get win rate for organization
     */
    public function getWinRateForOrganization(Organization $organization): float
    {
        $result = $this->createQueryBuilder('t')
            ->select('COALESCE(AVG(s.winRate), 0)')
            ->leftJoin('t.players', 'p')
            ->leftJoin('p.statistics', 's')
            ->andWhere('t.organization = :organization')
            ->setParameter('organization', $organization)
            ->getQuery()
            ->getSingleScalarResult();

        return (float) $result;
    }

    /**
     * Find distinct countries for filter dropdown
     * @return array<string>
     */
    public function findDistinctCountries(): array
    {
        $result = $this->createQueryBuilder('t')
            ->select('DISTINCT t.country')
            ->where('t.country IS NOT NULL')
            ->orderBy('t.country', 'ASC')
            ->getQuery()
            ->getSingleColumnResult();

        return $result;
    }
}
