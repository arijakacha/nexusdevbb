<?php

namespace App\EventSubscriber;

use App\Entity\CoachingSession;
use App\Entity\GameMatch;
use App\Entity\Notification;
use App\Entity\Product;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;

#[AsEntityListener(event: Events::postPersist, entity: GameMatch::class)]
#[AsEntityListener(event: Events::postPersist, entity: CoachingSession::class)]
#[AsEntityListener(event: Events::postUpdate, entity: Product::class)]
class NotificationSubscriber
{
    public function postPersist($entity, LifecycleEventArgs $args): void
    {
        $entityManager = $args->getObjectManager();

        if ($entity instanceof GameMatch) {
            $this->createMatchNotification($entity, $entityManager);
        } elseif ($entity instanceof CoachingSession) {
            $this->createCoachingNotification($entity, $entityManager);
        }
    }

    public function postUpdate($entity, LifecycleEventArgs $args): void
    {
        $entityManager = $args->getObjectManager();

        if ($entity instanceof Product) {
            $this->createLowStockNotification($entity, $entityManager);
        }
    }

    private function createMatchNotification(GameMatch $match, $entityManager): void
    {
        // Notify team members about upcoming matches
        $teamA = $match->getTeamA();
        $teamB = $match->getTeamB();

        if ($teamA) {
            foreach ($teamA->getPlayers() as $player) {
                if ($player->getUser()) {
                    $notification = new Notification();
                    $notification->setUser($player->getUser());
                    $notification->setMessage(sprintf(
                        'Upcoming match: %s vs %s on %s',
                        $teamA->getName(),
                        $teamB ? $teamB->getName() : 'TBD',
                        $match->getMatchDate()->format('M d, Y H:i')
                    ));
                    $notification->setIsRead(false);
                    $notification->setCreatedAt(new \DateTimeImmutable());
                    $entityManager->persist($notification);
                }
            }
        }

        $entityManager->flush();
    }

    private function createCoachingNotification(CoachingSession $session, $entityManager): void
    {
        $player = $session->getPlayer();
        $coach = $session->getCoach();

        if ($player && $player->getUser()) {
            $notification = new Notification();
            $notification->setUser($player->getUser());
            $notification->setMessage(sprintf(
                'Coaching session scheduled with %s on %s',
                $coach ? $coach->getUser()->getUsername() : 'Coach',
                $session->getScheduledAt()->format('M d, Y H:i')
            ));
            $notification->setIsRead(false);
            $notification->setCreatedAt(new \DateTimeImmutable());
            $entityManager->persist($notification);
        }

        $entityManager->flush();
    }

    private function createLowStockNotification(Product $product, $entityManager): void
    {
        if ($product->getQuantity() <= 5) {
            // Find admin users
            $adminUsers = $entityManager->getRepository(User::class)->findBy(['user_type' => 'ADMIN']);

            foreach ($adminUsers as $admin) {
                $notification = new Notification();
                $notification->setUser($admin);
                $notification->setMessage(sprintf(
                    'Low stock alert: %s only has %d units remaining',
                    $product->getName(),
                    $product->getQuantity()
                ));
                $notification->setIsRead(false);
                $notification->setCreatedAt(new \DateTimeImmutable());
                $entityManager->persist($notification);
            }

            $entityManager->flush();
        }
    }
}
