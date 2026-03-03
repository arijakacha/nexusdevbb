<?php

namespace App\Entity;

use App\Repository\StatisticRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StatisticRepository::class)]
class Statistic
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Game $game = null;

    #[ORM\ManyToOne]
    private ?Team $team = null;

    #[ORM\ManyToOne(inversedBy: 'statistics')]
    private ?Player $player = null;

    #[ORM\Column]
    private int $matchesPlayed = 0;

    #[ORM\Column]
    private int $wins = 0;

    #[ORM\Column]
    private int $losses = 0;

    #[ORM\Column]
    private int $kills = 0;

    #[ORM\Column]
    private int $deaths = 0;

    #[ORM\Column]
    private int $assists = 0;

    #[ORM\Column(type: 'decimal', precision: 5, scale: 2)]
    private string $winRate = '0.00';

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $rankTier = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $rankDivision = null;

    #[ORM\Column(nullable: true)]
    private ?int $leaguePoints = null;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getGame(): ?Game
    {
        return $this->game;
    }

    public function setGame(?Game $game): self
    {
        $this->game = $game;

        return $this;
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

    public function getPlayer(): ?Player
    {
        return $this->player;
    }

    public function setPlayer(?Player $player): self
    {
        $this->player = $player;

        return $this;
    }

    public function getMatchesPlayed(): int
    {
        return $this->matchesPlayed;
    }

    public function setMatchesPlayed(int $matchesPlayed): self
    {
        $this->matchesPlayed = $matchesPlayed;

        return $this;
    }

    public function getWins(): int
    {
        return $this->wins;
    }

    public function setWins(int $wins): self
    {
        $this->wins = $wins;

        return $this;
    }

    public function getLosses(): int
    {
        return $this->losses;
    }

    public function setLosses(int $losses): self
    {
        $this->losses = $losses;

        return $this;
    }

    public function getKills(): int
    {
        return $this->kills;
    }

    public function setKills(int $kills): self
    {
        $this->kills = $kills;

        return $this;
    }

    public function getDeaths(): int
    {
        return $this->deaths;
    }

    public function setDeaths(int $deaths): self
    {
        $this->deaths = $deaths;

        return $this;
    }

    public function getAssists(): int
    {
        return $this->assists;
    }

    public function setAssists(int $assists): self
    {
        $this->assists = $assists;

        return $this;
    }

    public function getWinRate(): string
    {
        return $this->winRate;
    }

    public function setWinRate(string $winRate): self
    {
        $this->winRate = $winRate;

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

    public function getRankTier(): ?string
    {
        return $this->rankTier;
    }

    public function setRankTier(?string $rankTier): self
    {
        $this->rankTier = $rankTier;

        return $this;
    }

    public function getRankDivision(): ?string
    {
        return $this->rankDivision;
    }

    public function setRankDivision(?string $rankDivision): self
    {
        $this->rankDivision = $rankDivision;

        return $this;
    }

    public function getLeaguePoints(): ?int
    {
        return $this->leaguePoints;
    }

    public function setLeaguePoints(?int $leaguePoints): self
    {
        $this->leaguePoints = $leaguePoints;

        return $this;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }
}
