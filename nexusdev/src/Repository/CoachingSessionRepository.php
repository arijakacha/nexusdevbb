<?php

namespace App\Repository;

use App\Entity\Coach;
use App\Entity\CoachingSession;
use App\Entity\Player;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CoachingSession>
 */
class CoachingSessionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CoachingSession::class);
    }

    /**
     * @return CoachingSession[]
     */
    public function findForCoachBetween(Coach $coach, \DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.coach = :coach')
            ->andWhere('s.scheduledAt >= :from')
            ->andWhere('s.scheduledAt < :to')
            ->setParameter('coach', $coach)
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->orderBy('s.scheduledAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get total sessions count for a coach
     */
    public function countTotalSessionsForCoach(Coach $coach): int
    {
        return (int) $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->andWhere('s.coach = :coach')
            ->setParameter('coach', $coach)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Get completed sessions count for a coach
     */
    public function countCompletedSessionsForCoach(Coach $coach): int
    {
        return (int) $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->andWhere('s.coach = :coach')
            ->andWhere('s.status = :status')
            ->setParameter('coach', $coach)
            ->setParameter('status', 'COMPLETED')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Get upcoming sessions count for a coach
     */
    public function countUpcomingSessionsForCoach(Coach $coach): int
    {
        return (int) $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->andWhere('s.coach = :coach')
            ->andWhere('s.scheduledAt >= :now')
            ->andWhere('s.status IN (:statuses)')
            ->setParameter('coach', $coach)
            ->setParameter('now', new \DateTimeImmutable())
            ->setParameter('statuses', ['PENDING', 'CONFIRMED'])
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Get all sessions for a coach (for history view)
     * @return CoachingSession[]
     */
    public function findAllSessionsForCoach(Coach $coach, ?int $limit = null): array
    {
        $qb = $this->createQueryBuilder('s')
            ->andWhere('s.coach = :coach')
            ->setParameter('coach', $coach)
            ->orderBy('s.scheduledAt', 'DESC');

        if ($limit) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Get sessions between coach and specific player
     * @return CoachingSession[]
     */
    public function findSessionsBetweenCoachAndPlayer(Coach $coach, Player $player): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.coach = :coach')
            ->andWhere('s.player = :player')
            ->setParameter('coach', $coach)
            ->setParameter('player', $player)
            ->orderBy('s.scheduledAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get sessions for a specific month (for calendar view)
     * @return CoachingSession[]
     */
    public function findForCoachInMonth(Coach $coach, int $year, int $month): array
    {
        $start = new \DateTimeImmutable("$year-$month-01 00:00:00");
        $end = $start->modify('+1 month');

        return $this->createQueryBuilder('s')
            ->andWhere('s.coach = :coach')
            ->andWhere('s.scheduledAt >= :start')
            ->andWhere('s.scheduledAt < :end')
            ->setParameter('coach', $coach)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->orderBy('s.scheduledAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get unique players coached by this coach
     * @return Player[]
     */
    public function findUniquePlayersForCoach(Coach $coach): array
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        return $qb->select('p')
            ->from(Player::class, 'p')
            ->where(
                $qb->expr()->in(
                    'p.id',
                    $this->getEntityManager()->createQueryBuilder()
                        ->select('IDENTITY(s.player)')
                        ->from(CoachingSession::class, 's')
                        ->where('s.coach = :coach')
                        ->getDQL()
                )
            )
            ->setParameter('coach', $coach)
            ->getQuery()
            ->getResult();
    }

    /**
     * Get upcoming sessions for a player
     * @return CoachingSession[]
     */
    public function findUpcomingSessionsForPlayer(Player $player): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.player = :player')
            ->andWhere('s.scheduledAt >= :now')
            ->andWhere('s.status IN (:statuses)')
            ->setParameter('player', $player)
            ->setParameter('now', new \DateTimeImmutable())
            ->setParameter('statuses', ['PENDING', 'CONFIRMED'])
            ->orderBy('s.scheduledAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get past sessions for a player
     * @return CoachingSession[]
     */
    public function findPastSessionsForPlayer(Player $player, ?int $limit = null): array
    {
        $qb = $this->createQueryBuilder('s')
            ->andWhere('s.player = :player')
            ->andWhere('s.scheduledAt < :now OR s.status = :completed')
            ->setParameter('player', $player)
            ->setParameter('now', new \DateTimeImmutable())
            ->setParameter('completed', 'COMPLETED')
            ->orderBy('s.scheduledAt', 'DESC');

        if ($limit) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Get all sessions for a player
     * @return CoachingSession[]
     */
    public function findAllSessionsForPlayer(Player $player): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.player = :player')
            ->setParameter('player', $player)
            ->orderBy('s.scheduledAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Count total sessions for a player
     */
    public function countTotalSessionsForPlayer(Player $player): int
    {
        return (int) $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->andWhere('s.player = :player')
            ->setParameter('player', $player)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Find sessions that need meetings created (starting soon and no active meeting)
     * @return CoachingSession[]
     */
    public function findSessionsNeedingMeetings(\DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.scheduledAt >= :from')
            ->andWhere('s.scheduledAt <= :to')
            ->andWhere('s.status IN (:statuses)')
            ->andWhere('(s.meetingUrl IS NULL OR s.meetingExpiresAt IS NULL OR s.meetingExpiresAt <= :now)')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->setParameter('statuses', ['PENDING', 'CONFIRMED'])
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('s.scheduledAt', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
