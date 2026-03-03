<?php

namespace App\Repository;

use App\Entity\Statistic;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Statistic>
 */
class StatisticRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Statistic::class);
    }

    /**
     * Find top players by win rate for a game (global rankings)
     */
    public function findTopPlayersByGame(int $gameId, int $limit = 100): array
    {
        return $this->createQueryBuilder('s')
            ->join('s.player', 'p')
            ->where('s.game = :gameId')
            ->andWhere('s.player IS NOT NULL')
            ->setParameter('gameId', $gameId)
            ->orderBy('s.winRate', 'DESC')
            ->addOrderBy('s.wins', 'DESC')
            ->addOrderBy('s.kills', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find player statistics for a specific game
     */
    public function findPlayerStats(int $playerId, int $gameId): ?Statistic
    {
        return $this->createQueryBuilder('s')
            ->where('s.player = :playerId')
            ->andWhere('s.game = :gameId')
            ->setParameter('playerId', $playerId)
            ->setParameter('gameId', $gameId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find statistics by team
     */
    public function findByTeam(int $teamId): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.team = :teamId')
            ->setParameter('teamId', $teamId)
            ->getQuery()
            ->getResult();
    }
}
