<?php

namespace App\Repository;

use App\Entity\Achievement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Achievement>
 */
class AchievementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Achievement::class);
    }

    public function findByGame(int $gameId): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.game = :gameId OR a.game IS NULL')
            ->setParameter('gameId', $gameId)
            ->orderBy('a.rarity', 'ASC')
            ->addOrderBy('a.points', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByType(string $type): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.type = :type')
            ->setParameter('type', $type)
            ->orderBy('a.requiredValue', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
