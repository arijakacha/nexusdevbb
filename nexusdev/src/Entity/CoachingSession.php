<?php

namespace App\Entity;

use App\Repository\CoachingSessionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CoachingSessionRepository::class)]
#[ORM\Table(name: 'coaching_session')]
class CoachingSession
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Player::class, inversedBy: 'coachingSessions')]
    #[ORM\JoinColumn(nullable: false)]
    private Player $player;

    #[ORM\ManyToOne(targetEntity: Coach::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Coach $coach;

    #[ORM\Column(name: 'scheduled_at')]
    private \DateTimeImmutable $scheduledAt;

    #[ORM\Column(length: 20)]
    private string $status = 'PENDING';

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(name: 'created_at')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'meeting_url', nullable: true)]
    private ?string $meetingUrl = null;

    #[ORM\Column(name: 'meeting_room', nullable: true)]
    private ?string $meetingRoom = null;

    #[ORM\Column(name: 'meeting_expires_at', nullable: true)]
    private ?\DateTimeImmutable $meetingExpiresAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

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

    public function getCoach(): Coach
    {
        return $this->coach;
    }

    public function setCoach(Coach $coach): self
    {
        $this->coach = $coach;

        return $this;
    }

    public function getScheduledAt(): \DateTimeImmutable
    {
        return $this->scheduledAt;
    }

    public function setScheduledAt(\DateTimeImmutable $scheduledAt): self
    {
        $this->scheduledAt = $scheduledAt;

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): self
    {
        $this->notes = $notes;

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

    public function getMeetingUrl(): ?string
    {
        return $this->meetingUrl;
    }

    public function setMeetingUrl(?string $meetingUrl): self
    {
        $this->meetingUrl = $meetingUrl;

        return $this;
    }

    public function getMeetingRoom(): ?string
    {
        return $this->meetingRoom;
    }

    public function setMeetingRoom(?string $meetingRoom): self
    {
        $this->meetingRoom = $meetingRoom;

        return $this;
    }

    public function getMeetingExpiresAt(): ?\DateTimeImmutable
    {
        return $this->meetingExpiresAt;
    }

    public function setMeetingExpiresAt(?\DateTimeImmutable $meetingExpiresAt): self
    {
        $this->meetingExpiresAt = $meetingExpiresAt;

        return $this;
    }

    public function isMeetingActive(): bool
    {
        if (!$this->meetingExpiresAt) {
            return false;
        }
        
        return $this->meetingExpiresAt > new \DateTimeImmutable();
    }
}
