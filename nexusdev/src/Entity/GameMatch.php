<?php

namespace App\Entity;

use App\Repository\GameMatchRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GameMatchRepository::class)]
#[ORM\Table(name: 'game_match')]
class GameMatch
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Game::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Game $game;

    #[ORM\ManyToOne(targetEntity: Team::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Team $teamA = null;

    #[ORM\ManyToOne(targetEntity: Team::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Team $teamB = null;

    #[ORM\Column(length: 100, nullable: true, name: 'team_a_name')]
    private ?string $teamAName = null;

    #[ORM\Column(length: 100, nullable: true, name: 'team_b_name')]
    private ?string $teamBName = null;

    #[ORM\Column(nullable: true, name: 'team_a_score')]
    private ?int $teamAScore = null;

    #[ORM\Column(nullable: true, name: 'team_b_score')]
    private ?int $teamBScore = null;

    #[ORM\Column(length: 50)]
    private string $status = 'scheduled';

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $matchDate = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\OneToMany(targetEntity: MatchPlayer::class, mappedBy: 'gameMatch', cascade: ['persist', 'remove'])]
    private Collection $matchPlayers;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $map = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $replayUrl = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->matchPlayers = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getTeamA(): ?Team
    {
        return $this->teamA;
    }

    public function setTeamA(?Team $teamA): self
    {
        $this->teamA = $teamA;
        return $this;
    }

    public function getTeamB(): ?Team
    {
        return $this->teamB;
    }

    public function setTeamB(?Team $teamB): self
    {
        $this->teamB = $teamB;
        return $this;
    }

    public function getTeamAName(): ?string
    {
        return $this->teamAName;
    }

    public function setTeamAName(?string $teamAName): self
    {
        $this->teamAName = $teamAName;
        return $this;
    }

    public function getTeamBName(): ?string
    {
        return $this->teamBName;
    }

    public function setTeamBName(?string $teamBName): self
    {
        $this->teamBName = $teamBName;
        return $this;
    }

    public function getTeamAScore(): ?int
    {
        return $this->teamAScore;
    }

    public function setTeamAScore(?int $teamAScore): self
    {
        $this->teamAScore = $teamAScore;
        return $this;
    }

    public function getTeamBScore(): ?int
    {
        return $this->teamBScore;
    }

    public function setTeamBScore(?int $teamBScore): self
    {
        $this->teamBScore = $teamBScore;
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

    public function getMatchDate(): ?\DateTimeImmutable
    {
        return $this->matchDate;
    }

    public function setMatchDate(?\DateTimeImmutable $matchDate): self
    {
        $this->matchDate = $matchDate;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getMatchPlayers(): Collection
    {
        return $this->matchPlayers;
    }

    public function getMap(): ?string
    {
        return $this->map;
    }

    public function setMap(?string $map): self
    {
        $this->map = $map;
        return $this;
    }

    public function getReplayUrl(): ?string
    {
        return $this->replayUrl;
    }

    public function setReplayUrl(?string $replayUrl): self
    {
        $this->replayUrl = $replayUrl;
        return $this;
    }
}
