<?php

namespace App\Repository;

use App\Entity\GameMatch;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<GameMatch>
 */
class GameMatchRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GameMatch::class);
    }

    public function findByGame(int $gameId, int $limit = 50): array
    {
        return $this->createQueryBuilder('gm')
            ->where('gm.game = :gameId')
            ->setParameter('gameId', $gameId)
            ->orderBy('gm.matchDate', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findByTeam(int $teamId, int $limit = 50): array
    {
        return $this->createQueryBuilder('gm')
            ->where('gm.teamA = :teamId OR gm.teamB = :teamId')
            ->setParameter('teamId', $teamId)
            ->orderBy('gm.matchDate', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findByPlayer(int $playerId, int $limit = 50): array
    {
        return $this->createQueryBuilder('gm')
            ->select('gm', 'mp')
            ->join('gm.matchPlayers', 'mp')
            ->where('mp.player = :playerId')
            ->setParameter('playerId', $playerId)
            ->orderBy('gm.matchDate', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findCompletedMatches(int $gameId, \DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        return $this->createQueryBuilder('gm')
            ->where('gm.game = :gameId')
            ->andWhere('gm.status = :status')
            ->andWhere('gm.matchDate BETWEEN :start AND :end')
            ->setParameter('gameId', $gameId)
            ->setParameter('status', 'completed')
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate)
            ->orderBy('gm.matchDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findMatchesWithReplays(int $gameId, int $limit = 20): array
    {
        return $this->createQueryBuilder('gm')
            ->where('gm.game = :gameId')
            ->andWhere('gm.replayUrl IS NOT NULL')
            ->andWhere('gm.status = :status')
            ->setParameter('gameId', $gameId)
            ->setParameter('status', 'completed')
            ->orderBy('gm.matchDate', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
