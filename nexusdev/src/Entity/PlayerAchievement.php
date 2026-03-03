<?php

namespace App\Entity;

use App\Repository\PlayerAchievementRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PlayerAchievementRepository::class)]
#[ORM\Table(name: 'player_achievement')]
class PlayerAchievement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Player::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Player $player;

    #[ORM\ManyToOne(targetEntity: Achievement::class, inversedBy: 'playerAchievements')]
    #[ORM\JoinColumn(nullable: false)]
    private Achievement $achievement;

    #[ORM\Column]
    private int $progress = 0;

    #[ORM\Column]
    private bool $isUnlocked = false;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $unlockedAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPlayer(): Player
    {
        return $this->player;
    }

    public function setPlayer(Player $player): self
    {
        $this->player = $player;
        return $this;
    }

    public function getAchievement(): Achievement
    {
        return $this->achievement;
    }

    public function setAchievement(Achievement $achievement): self
    {
        $this->achievement = $achievement;
        return $this;
    }

    public function getProgress(): int
    {
        return $this->progress;
    }

    public function setProgress(int $progress): self
    {
        $this->progress = $progress;
        return $this;
    }

    public function isUnlocked(): bool
    {
        return $this->isUnlocked;
    }

    public function setIsUnlocked(bool $isUnlocked): self
    {
        $this->isUnlocked = $isUnlocked;
        return $this;
    }

    public function getUnlockedAt(): ?\DateTimeImmutable
    {
        return $this->unlockedAt;
    }

    public function setUnlockedAt(?\DateTimeImmutable $unlockedAt): self
    {
        $this->unlockedAt = $unlockedAt;
        return $this;
    }
}
