<?php

namespace App\Repository;

use App\Entity\Conversation;
use App\Entity\Message;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Conversation>
 */
class ConversationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Conversation::class);
    }

    public function save(Conversation $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Conversation $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return Conversation[]
     */
    public function findConversationsForUser(User $user): array
    {
        return $this->createQueryBuilder('c')
            ->select('DISTINCT c')
            ->innerJoin('c.participants', 'p')
            ->addSelect('p')
            ->where('p = :user')
            ->setParameter('user', $user)
            ->orderBy('c.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findConversationBetweenUsers(User $user1, User $user2): ?Conversation
    {
        return $this->createQueryBuilder('c')
            ->innerJoin('c.participants', 'p')
            ->where('p.id IN (:ids)')
            ->setParameter('ids', [$user1->getId(), $user2->getId()])
            ->groupBy('c.id')
            ->having('COUNT(DISTINCT p.id) = 2')
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return array<int, int> [conversationId => unreadCount]
     */
    public function getUnreadCountsByConversationForUser(User $user): array
    {
        $rows = $this->createQueryBuilder('c')
            ->select('c.id AS conversationId')
            ->addSelect('COUNT(m.id) AS unreadCount')
            ->innerJoin('c.participants', 'p')
            ->innerJoin('c.messages', 'm')
            ->where('p.id = :userId')
            ->andWhere('m.sender != :user')
            ->andWhere('m.readAt IS NULL')
            ->setParameter('userId', $user->getId())
            ->setParameter('user', $user)
            ->groupBy('c.id')
            ->getQuery()
            ->getArrayResult();

        $result = [];
        foreach ($rows as $row) {
            $result[(int) $row['conversationId']] = (int) $row['unreadCount'];
        }

        return $result;
    }

    /**
     * @return array<int, Message[]> [conversationId => Message[]]
     */
    public function getLastMessagesByConversationForUser(User $user, int $perConversation = 3): array
    {
        $conversations = $this->findConversationsForUser($user);
        if ($conversations === []) {
            return [];
        }

        $conversationIds = array_map(static fn(Conversation $c) => $c->getId(), $conversations);
        $conversationIds = array_values(array_filter($conversationIds, static fn($id) => $id !== null));
        if ($conversationIds === []) {
            return [];
        }

        $allMessages = $this->getEntityManager()->getRepository(Message::class)
            ->createQueryBuilder('m')
            ->leftJoin('m.sender', 's')
            ->addSelect('s', 'IDENTITY(m.conversation) AS conversationId')
            ->where('m.conversation IN (:ids)')
            ->setParameter('ids', $conversationIds)
            ->orderBy('m.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        $result = [];
        foreach ($allMessages as $row) {
            if (!is_array($row) || !isset($row[0], $row['conversationId'])) {
                continue;
            }
            $message = $row[0];
            $conversationId = (int) $row['conversationId'];

            $result[$conversationId] ??= [];
            if (count($result[$conversationId]) >= $perConversation) {
                continue;
            }
            $result[$conversationId][] = $message;
        }

        foreach ($result as $conversationId => $messages) {
            $result[$conversationId] = array_reverse($messages);
        }

        return $result;
    }
}
