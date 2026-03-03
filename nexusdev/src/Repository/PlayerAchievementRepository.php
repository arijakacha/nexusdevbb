<?php

namespace App\Repository;

use App\Entity\PlayerAchievement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PlayerAchievement>
 */
class PlayerAchievementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PlayerAchievement::class);
    }

    public function findByPlayer(int $playerId): array
    {
        return $this->createQueryBuilder('pa')
            ->select('pa', 'a')
            ->join('pa.achievement', 'a')
            ->where('pa.player = :playerId')
            ->setParameter('playerId', $playerId)
            ->orderBy('pa.isUnlocked', 'DESC')
            ->addOrderBy('pa.unlockedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findUnlockedByPlayer(int $playerId): array
    {
        return $this->createQueryBuilder('pa')
            ->select('pa', 'a')
            ->join('pa.achievement', 'a')
            ->where('pa.player = :playerId')
            ->andWhere('pa.isUnlocked = :unlocked')
            ->setParameter('playerId', $playerId)
            ->setParameter('unlocked', true)
            ->orderBy('pa.unlockedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findPlayerAchievement(int $playerId, int $achievementId): ?PlayerAchievement
    {
        return $this->createQueryBuilder('pa')
            ->where('pa.player = :playerId')
            ->andWhere('pa.achievement = :achievementId')
            ->setParameter('playerId', $playerId)
            ->setParameter('achievementId', $achievementId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function getTotalPointsByPlayer(int $playerId): int
    {
        $result = $this->createQueryBuilder('pa')
            ->select('SUM(a.points)')
            ->join('pa.achievement', 'a')
            ->where('pa.player = :playerId')
            ->andWhere('pa.isUnlocked = :unlocked')
            ->setParameter('playerId', $playerId)
            ->setParameter('unlocked', true)
            ->getQuery()
            ->getSingleScalarResult();

        return (int) ($result ?? 0);
    }
}
