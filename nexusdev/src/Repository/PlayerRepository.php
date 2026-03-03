<?php

namespace App\Repository;

use App\Entity\Player;
use App\Entity\Team;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Player>
 */
class PlayerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Player::class);
    }

    /**
     * @return Player[]
     */
    public function findProPlayers(): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.isPro = :pro')
            ->setParameter('pro', true)
            ->orderBy('p.score', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find available PRO players (not in any team)
     * @return Player[]
     */
    public function findAvailableProPlayers(): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.isPro = :pro')
            ->andWhere('p.team IS NULL')
            ->setParameter('pro', true)
            ->orderBy('p.score', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get players by team
     * @return Player[]
     */
    public function findByTeam(Team $team): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.team = :team')
            ->setParameter('team', $team)
            ->orderBy('p.score', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get top players by score
     * @return Player[]
     */
    public function findTopPlayers(int $limit = 10): array
    {
        return $this->createQueryBuilder('p')
            ->orderBy('p.score', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Search players by nickname or real name
     * @return Player[]
     */
    public function searchPlayers(string $query): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.nickname LIKE :query OR p.realName LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('p.score', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Count PRO players available for recruitment
     */
    public function countAvailableProPlayers(): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->andWhere('p.isPro = :pro')
            ->andWhere('p.team IS NULL')
            ->setParameter('pro', true)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Find player by associated User entity
     */
    public function findByUser(User $user): ?Player
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Get players without a team (free agents)
     * @return Player[]
     */
    public function findFreeAgents(): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.team IS NULL')
            ->orderBy('p.score', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
