<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        if ($user->getStatus() === 'BANNED') {
            throw new CustomUserMessageAccountStatusException('Your account has been blocked. Please contact support.');
        }
    }

    public function checkPostAuth(UserInterface $user): void
    {
        // No post-auth checks needed
    }
}
