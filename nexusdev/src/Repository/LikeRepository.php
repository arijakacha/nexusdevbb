<?php

namespace App\Repository;

use App\Entity\ForumPost;
use App\Entity\Like;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Like>
 */
class LikeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Like::class);
    }

    public function findByUserAndPost(User $user, ForumPost $post): ?Like
    {
        return $this->createQueryBuilder('l')
            ->where('l.user = :user')
            ->andWhere('l.post = :post')
            ->setParameter('user', $user)
            ->setParameter('post', $post)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function countLikesByPost(ForumPost $post): int
    {
        return $this->createQueryBuilder('l')
            ->select('COUNT(l.id)')
            ->where('l.post = :post')
            ->andWhere('l.type = :type')
            ->setParameter('post', $post)
            ->setParameter('type', 'like')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countDislikesByPost(ForumPost $post): int
    {
        return $this->createQueryBuilder('l')
            ->select('COUNT(l.id)')
            ->where('l.post = :post')
            ->andWhere('l.type = :type')
            ->setParameter('post', $post)
            ->setParameter('type', 'dislike')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function hasUserLiked(User $user, ForumPost $post): bool
    {
        $like = $this->findByUserAndPost($user, $post);
        return $like !== null && $like->getType() === 'like';
    }

    public function hasUserDisliked(User $user, ForumPost $post): bool
    {
        $like = $this->findByUserAndPost($user, $post);
        return $like !== null && $like->getType() === 'dislike';
    }
}
