<?php

namespace App\Repository;

use App\Entity\RankHistory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<RankHistory>
 */
class RankHistoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RankHistory::class);
    }

    public function findPlayerRankHistory(int $playerId, int $gameId, ?string $region = null, ?string $season = null): array
    {
        $qb = $this->createQueryBuilder('rh')
            ->where('rh.player = :playerId')
            ->andWhere('rh.game = :gameId')
            ->setParameter('playerId', $playerId)
            ->setParameter('gameId', $gameId)
            ->orderBy('rh.recordedAt', 'ASC');

        if ($region) {
            $qb->andWhere('rh.region = :region')
                ->setParameter('region', $region);
        }

        if ($season) {
            $qb->andWhere('rh.season = :season')
                ->setParameter('season', $season);
        }

        return $qb->getQuery()->getResult();
    }

    public function findGlobalRankings(int $gameId, ?string $season = null, int $limit = 100): array
    {
        $qb = $this->createQueryBuilder('rh')
            ->select('rh', 'p', 'g')
            ->join('rh.player', 'p')
            ->join('rh.game', 'g')
            ->where('rh.game = :gameId')
            ->andWhere('rh.region IS NULL')
            ->setParameter('gameId', $gameId)
            ->orderBy('rh.eloRating', 'DESC')
            ->addOrderBy('rh.recordedAt', 'DESC')
            ->setMaxResults($limit);

        if ($season) {
            $qb->andWhere('rh.season = :season')
                ->setParameter('season', $season);
        }

        return $qb->getQuery()->getResult();
    }

    public function findRegionalRankings(int $gameId, string $region, ?string $season = null, int $limit = 100): array
    {
        $qb = $this->createQueryBuilder('rh')
            ->select('rh', 'p', 'g')
            ->join('rh.player', 'p')
            ->join('rh.game', 'g')
            ->where('rh.game = :gameId')
            ->andWhere('rh.region = :region')
            ->setParameter('gameId', $gameId)
            ->setParameter('region', $region)
            ->orderBy('rh.eloRating', 'DESC')
            ->addOrderBy('rh.recordedAt', 'DESC')
            ->setMaxResults($limit);

        if ($season) {
            $qb->andWhere('rh.season = :season')
                ->setParameter('season', $season);
        }

        return $qb->getQuery()->getResult();
    }

    public function findLatestPlayerRank(int $playerId, int $gameId): ?RankHistory
    {
        return $this->createQueryBuilder('rh')
            ->where('rh.player = :playerId')
            ->andWhere('rh.game = :gameId')
            ->setParameter('playerId', $playerId)
            ->setParameter('gameId', $gameId)
            ->orderBy('rh.recordedAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
