<?php

namespace App\Security\Voter;

use App\Entity\Player;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class PlayerProfileVoter extends Voter
{
    public const EDIT = 'PLAYER_PROFILE_EDIT';
    public const VIEW = 'PLAYER_PROFILE_VIEW';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::EDIT, self::VIEW])
            && $subject instanceof Player;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        // User must be logged in
        if (!$user instanceof User) {
            return false;
        }

        /** @var Player $player */
        $player = $subject;

        switch ($attribute) {
            case self::EDIT:
                // Only the player themselves can edit their profile
                return $this->canEdit($player, $user);
            case self::VIEW:
                // Anyone can view, but we'll check if it's their own for special features
                return $this->canView($player, $user);
        }

        return false;
    }

    private function canEdit(Player $player, User $user): bool
    {
        // Check if the player belongs to this user
        return $player->getUser() === $user;
    }

    private function canView(Player $player, User $user): bool
    {
        // Anyone can view player profiles
        // But we could restrict private profiles here if needed
        return true;
    }
}
