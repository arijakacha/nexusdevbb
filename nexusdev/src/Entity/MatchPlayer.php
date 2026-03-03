<?php

namespace App\Entity;

use App\Repository\MatchPlayerRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MatchPlayerRepository::class)]
class MatchPlayer
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: GameMatch::class, inversedBy: 'matchPlayers')]
    #[ORM\JoinColumn(nullable: false)]
    private GameMatch $gameMatch;

    #[ORM\ManyToOne(targetEntity: Player::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Player $player;

    #[ORM\ManyToOne(targetEntity: Team::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Team $team = null;

    #[ORM\Column]
    private int $kills = 0;

    #[ORM\Column]
    private int $deaths = 0;

    #[ORM\Column]
    private int $assists = 0;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?string $positionX = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?string $positionY = null;

    #[ORM\Column]
    private bool $isWinner = false;

    #[ORM\Column(nullable: true)]
    private ?int $eloChange = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getGameMatch(): GameMatch
    {
        return $this->gameMatch;
    }

    public function setGameMatch(GameMatch $gameMatch): self
    {
        $this->gameMatch = $gameMatch;
        return $this;
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

    public function getTeam(): ?Team
    {
        return $this->team;
    }

    public function setTeam(?Team $team): self
    {
        $this->team = $team;
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

    public function getPositionX(): ?string
    {
        return $this->positionX;
    }

    public function setPositionX(?string $positionX): self
    {
        $this->positionX = $positionX;
        return $this;
    }

    public function getPositionY(): ?string
    {
        return $this->positionY;
    }

    public function setPositionY(?string $positionY): self
    {
        $this->positionY = $positionY;
        return $this;
    }

    public function isWinner(): bool
    {
        return $this->isWinner;
    }

    public function setIsWinner(bool $isWinner): self
    {
        $this->isWinner = $isWinner;
        return $this;
    }

    public function getEloChange(): ?int
    {
        return $this->eloChange;
    }

    public function setEloChange(?int $eloChange): self
    {
        $this->eloChange = $eloChange;
        return $this;
    }
}
