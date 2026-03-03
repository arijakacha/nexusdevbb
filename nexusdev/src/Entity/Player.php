<?php

namespace App\Entity;

use App\Repository\PlayerRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PlayerRepository::class)]
class Player
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Team::class, inversedBy: 'players')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Team $team = null;

    #[ORM\OneToOne(inversedBy: 'player', targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Game::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Game $game;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    private string $nickname;

    #[ORM\Column(length: 150, nullable: true)]
    #[Assert\Length(max: 150)]
    private ?string $realName = null;

    #[ORM\Column(length: 80, nullable: true)]
    #[Assert\Length(max: 80)]
    private ?string $role = null;

    #[ORM\Column(length: 80, nullable: true)]
    #[Assert\Length(max: 80)]
    private ?string $nationality = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $profilePicture = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Assert\Length(max: 20)]
    private ?string $phoneNumber = null;

    #[ORM\Column(name: 'sms_consent', type: 'boolean')]
    private bool $smsConsent = false;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private int $score = 0;

    #[ORM\Column(name: 'is_pro')]
    private bool $isPro = false;

    #[ORM\OneToMany(targetEntity: Statistic::class, mappedBy: 'player')]
    private Collection $statistics;

    #[ORM\OneToMany(targetEntity: CoachingSession::class, mappedBy: 'player')]
    private Collection $coachingSessions;

    #[ORM\OneToMany(targetEntity: TeamInvitation::class, mappedBy: 'player', orphanRemoval: true)]
    private Collection $teamInvitations;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->statistics = new ArrayCollection();
        $this->coachingSessions = new ArrayCollection();
        $this->teamInvitations = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTeam(): ?Team
    {
        return $this->team;
    }

    public function setTeam(?Team $team): self
    {
        $this->team = $team;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getGame(): Game
    {
        return $this->game;
    }

    public function setGame(Game $game): self
    {
        $this->game = $game;

        return $this;
    }

    public function getNickname(): string
    {
        return $this->nickname;
    }

    public function setNickname(string $nickname): self
    {
        $this->nickname = $nickname;

        return $this;
    }

    public function getRealName(): ?string
    {
        return $this->realName;
    }

    public function setRealName(?string $realName): self
    {
        $this->realName = $realName;

        return $this;
    }

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function setRole(?string $role): self
    {
        $this->role = $role;

        return $this;
    }

    public function getNationality(): ?string
    {
        return $this->nationality;
    }

    public function setNationality(?string $nationality): self
    {
        $this->nationality = $nationality;

        return $this;
    }

    public function getProfilePicture(): ?string
    {
        return $this->profilePicture;
    }

    public function setProfilePicture(?string $profilePicture): self
    {
        $this->profilePicture = $profilePicture;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getScore(): int
    {
        return $this->score;
    }

    public function setScore(int $score): self
    {
        $this->score = $score;

        return $this;
    }

    public function isPro(): bool
    {
        return $this->isPro;
    }

    public function setIsPro(bool $isPro): self
    {
        $this->isPro = $isPro;

        return $this;
    }

    // Helper method to calculate rank based on score
    public function getRank(): string
    {
        $score = $this->score;
        if ($score >= 2000) {
            return 'Diamond';
        } elseif ($score >= 1500) {
            return 'Platinum';
        } elseif ($score >= 1000) {
            return 'Gold';
        } elseif ($score >= 500) {
            return 'Silver';
        }
        return 'Bronze';
    }

    /**
     * @return Collection<int, Statistic>
     */
    public function getStatistics(): Collection
    {
        return $this->statistics;
    }

    public function addStatistic(Statistic $statistic): static
    {
        if (!$this->statistics->contains($statistic)) {
            $this->statistics->add($statistic);
            $statistic->setPlayer($this);
        }
        return $this;
    }

    public function removeStatistic(Statistic $statistic): static
    {
        if ($this->statistics->removeElement($statistic)) {
            // set the owning side to null (unless already changed)
            if ($statistic->getPlayer() === $this) {
                $statistic->setPlayer(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, CoachingSession>
     */
    public function getCoachingSessions(): Collection
    {
        return $this->coachingSessions;
    }

    public function addCoachingSession(CoachingSession $coachingSession): static
    {
        if (!$this->coachingSessions->contains($coachingSession)) {
            $this->coachingSessions->add($coachingSession);
            $coachingSession->setPlayer($this);
        }
        return $this;
    }

    public function removeCoachingSession(CoachingSession $coachingSession): static
    {
        if ($this->coachingSessions->removeElement($coachingSession)) {
            // set the owning side to null (unless already changed)
            if ($coachingSession->getPlayer() === $this) {
                $coachingSession->setPlayer(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, TeamInvitation>
     */
    public function getTeamInvitations(): Collection
    {
        return $this->teamInvitations;
    }

    public function addTeamInvitation(TeamInvitation $teamInvitation): static
    {
        if (!$this->teamInvitations->contains($teamInvitation)) {
            $this->teamInvitations->add($teamInvitation);
            $teamInvitation->setPlayer($this);
        }
        return $this;
    }

    public function removeTeamInvitation(TeamInvitation $teamInvitation): static
    {
        if ($this->teamInvitations->removeElement($teamInvitation)) {
            if ($teamInvitation->getPlayer() === $this) {
                $teamInvitation->setPlayer(null);
            }
        }
        return $this;
    }

    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    public function setPhoneNumber(?string $phoneNumber): self
    {
        $this->phoneNumber = $phoneNumber;

        return $this;
    }

    public function isSmsConsent(): bool
    {
        return $this->smsConsent;
    }

    public function setSmsConsent(bool $smsConsent): self
    {
        $this->smsConsent = $smsConsent;

        return $this;
    }

    public function canReceiveSms(): bool
    {
        return $this->smsConsent && !empty($this->phoneNumber);
    }
}
