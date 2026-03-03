<?php

namespace App\Repository;

use App\Entity\MatchPlayer;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MatchPlayer>
 */
class MatchPlayerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MatchPlayer::class);
    }

    public function findByPlayer(int $playerId, int $limit = 50): array
    {
        return $this->createQueryBuilder('mp')
            ->select('mp', 'gm')
            ->join('mp.gameMatch', 'gm')
            ->where('mp.player = :playerId')
            ->setParameter('playerId', $playerId)
            ->orderBy('gm.matchDate', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findPlayerPerformanceStats(int $playerId, \DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        return $this->createQueryBuilder('mp')
            ->select(
                'SUM(mp.kills) as totalKills',
                'SUM(mp.deaths) as totalDeaths',
                'SUM(mp.assists) as totalAssists',
                'COUNT(mp.id) as totalMatches',
                'SUM(CASE WHEN mp.isWinner = true THEN 1 ELSE 0 END) as wins'
            )
            ->join('mp.gameMatch', 'gm')
            ->where('mp.player = :playerId')
            ->andWhere('gm.matchDate BETWEEN :start AND :end')
            ->setParameter('playerId', $playerId)
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate)
            ->getQuery()
            ->getSingleResult();
    }

    public function findHeatMapData(int $playerId, int $gameId): array
    {
        return $this->createQueryBuilder('mp')
            ->select('mp.positionX', 'mp.positionY', 'COUNT(mp.id) as frequency')
            ->join('mp.gameMatch', 'gm')
            ->where('mp.player = :playerId')
            ->andWhere('gm.game = :gameId')
            ->andWhere('mp.positionX IS NOT NULL')
            ->andWhere('mp.positionY IS NOT NULL')
            ->setParameter('playerId', $playerId)
            ->setParameter('gameId', $gameId)
            ->groupBy('mp.positionX', 'mp.positionY')
            ->getQuery()
            ->getResult();
    }

    public function findTeamSynergyStats(int $teamId, int $gameId): array
    {
        return $this->createQueryBuilder('mp')
            ->select(
                'p.nickname',
                'SUM(mp.kills) as totalKills',
                'SUM(mp.assists) as totalAssists',
                'AVG(mp.kills) as avgKills',
                'SUM(CASE WHEN mp.isWinner = true THEN 1 ELSE 0 END) as wins',
                'COUNT(mp.id) as totalMatches'
            )
            ->join('mp.player', 'p')
            ->join('mp.gameMatch', 'gm')
            ->where('mp.team = :teamId')
            ->andWhere('gm.game = :gameId')
            ->setParameter('teamId', $teamId)
            ->setParameter('gameId', $gameId)
            ->groupBy('p.id', 'p.nickname')
            ->getQuery()
            ->getResult();
    }
}
