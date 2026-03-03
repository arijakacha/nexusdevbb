<?php

namespace App\Repository;

use App\Entity\Player;
use App\Entity\Team;
use App\Entity\TeamInvitation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TeamInvitation>
 */
class TeamInvitationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TeamInvitation::class);
    }

    public function findPendingForPlayer(Player $player): array
    {
        return $this->createQueryBuilder('ti')
            ->andWhere('ti.player = :player')
            ->andWhere('ti.status = :status')
            ->setParameter('player', $player)
            ->setParameter('status', TeamInvitation::STATUS_PENDING)
            ->orderBy('ti.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByTeamAndPlayer(Team $team, Player $player): ?TeamInvitation
    {
        return $this->createQueryBuilder('ti')
            ->andWhere('ti.team = :team')
            ->andWhere('ti.player = :player')
            ->andWhere('ti.status = :status')
            ->setParameter('team', $team)
            ->setParameter('player', $player)
            ->setParameter('status', TeamInvitation::STATUS_PENDING)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findExistingPendingInvitation(Player $player, Team $team): ?TeamInvitation
    {
        return $this->createQueryBuilder('ti')
            ->andWhere('ti.player = :player')
            ->andWhere('ti.team = :team')
            ->andWhere('ti.status = :status')
            ->setParameter('player', $player)
            ->setParameter('team', $team)
            ->setParameter('status', TeamInvitation::STATUS_PENDING)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function hasPendingInvitation(Team $team, Player $player): bool
    {
        return $this->findByTeamAndPlayer($team, $player) !== null;
    }
}
