<?php

namespace App\Entity;

use App\Entity\Player;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 3, max: 100)]
    private string $username;

    #[ORM\Column(length: 180, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Email]
    #[Assert\Length(max: 180)]
    private string $email;

    #[ORM\Column]
    #[Assert\NotBlank]
    #[Assert\Length(min: 6)]
    private string $password;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $profilePicture = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(length: 30)]
    #[Assert\NotBlank]
    #[Assert\Choice(choices: ['ACTIVE', 'BANNED'])]
    private string $status = 'ACTIVE';

    #[ORM\Column(length: 20)]
    #[Assert\NotBlank]
    #[Assert\Choice(choices: ['VISITOR', 'REGISTERED', 'COACH', 'ORGANIZATION', 'ADMIN'])]
    private string $userType = 'REGISTERED';

    #[ORM\OneToOne(mappedBy: 'user', targetEntity: Coach::class)]
    private ?Coach $coach = null;

    #[ORM\OneToOne(mappedBy: 'user', targetEntity: Player::class)]
    private ?Player $player = null;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Order::class)]
    private Collection $orders;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: VirtualCurrency::class)]
    private Collection $virtualCurrencies;

    #[ORM\OneToMany(mappedBy: 'author', targetEntity: Content::class)]
    private Collection $contents;

    #[ORM\OneToMany(mappedBy: 'author', targetEntity: ForumPost::class)]
    private Collection $forumPosts;

    #[ORM\OneToMany(mappedBy: 'author', targetEntity: Comment::class)]
    private Collection $comments;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Notification::class)]
    private Collection $notifications;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: ProductPurchase::class)]
    private Collection $productPurchases;

    #[ORM\Column(type: 'boolean')]
    private bool $hasPlayer = false;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $riotSummonerName = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $riotRegion = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $riotPuuid = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $riotSummonerId = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $riotLastSyncAt = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $recentMatches = null;

    private ?int $unreadMessageCount = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->orders = new ArrayCollection();
        $this->virtualCurrencies = new ArrayCollection();
        $this->contents = new ArrayCollection();
        $this->forumPosts = new ArrayCollection();
        $this->comments = new ArrayCollection();
        $this->notifications = new ArrayCollection();
        $this->productPurchases = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

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

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getRoles(): array
    {
        $roles = ['ROLE_USER'];

        switch ($this->userType) {
            case 'ADMIN':
                $roles[] = 'ROLE_ADMIN';
                break;
            case 'COACH':
                $roles[] = 'ROLE_COACH';
                break;
            case 'ORGANIZATION':
                $roles[] = 'ROLE_ORGANIZATION';
                break;
            case 'VISITOR':
                $roles[] = 'ROLE_VISITOR';
                break;
            case 'REGISTERED':
            default:
                break;
        }

        return array_values(array_unique($roles));
    }

    public function eraseCredentials(): void
    {
    }

    public function getUserType(): string
    {
        return $this->userType;
    }

    public function setUserType(string $userType): self
    {
        $this->userType = $userType;

        return $this;
    }

    public function getCoach(): ?Coach
    {
        return $this->coach;
    }

    public function setCoach(?Coach $coach): self
    {
        $this->coach = $coach;

        return $this;
    }

    public function getOrders(): Collection
    {
        return $this->orders;
    }

    public function getVirtualCurrencies(): Collection
    {
        return $this->virtualCurrencies;
    }

    public function getContents(): Collection
    {
        return $this->contents;
    }

    public function getForumPosts(): Collection
    {
        return $this->forumPosts;
    }

    public function getComments(): Collection
    {
        return $this->comments;
    }

    public function getNotifications(): Collection
    {
        return $this->notifications;
    }

    public function getProductPurchases(): Collection
    {
        return $this->productPurchases;
    }

    public function hasPlayer(): bool
    {
        return $this->hasPlayer;
    }

    public function setHasPlayer(bool $hasPlayer): self
    {
        $this->hasPlayer = $hasPlayer;

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

    public function getRiotSummonerName(): ?string
    {
        return $this->riotSummonerName;
    }

    public function setRiotSummonerName(?string $riotSummonerName): self
    {
        $this->riotSummonerName = $riotSummonerName;

        return $this;
    }

    public function getRiotRegion(): ?string
    {
        return $this->riotRegion;
    }

    public function setRiotRegion(?string $riotRegion): self
    {
        $this->riotRegion = $riotRegion;

        return $this;
    }

    public function getRiotPuuid(): ?string
    {
        return $this->riotPuuid;
    }

    public function setRiotPuuid(?string $riotPuuid): self
    {
        $this->riotPuuid = $riotPuuid;

        return $this;
    }

    public function getRiotSummonerId(): ?string
    {
        return $this->riotSummonerId;
    }

    public function setRiotSummonerId(?string $riotSummonerId): self
    {
        $this->riotSummonerId = $riotSummonerId;

        return $this;
    }

    public function getRiotLastSyncAt(): ?\DateTimeImmutable
    {
        return $this->riotLastSyncAt;
    }

    public function setRiotLastSyncAt(?\DateTimeImmutable $riotLastSyncAt): self
    {
        $this->riotLastSyncAt = $riotLastSyncAt;

        return $this;
    }

    public function getUnreadMessageCount(): int
    {
        // This will be set by a listener or manually when needed
        return $this->unreadMessageCount ?? 0;
    }

    public function setUnreadMessageCount(int $count): self
    {
        $this->unreadMessageCount = $count;
        return $this;
    }

    public function getRecentMatches(): ?array
    {
        return $this->recentMatches;
    }

    public function setRecentMatches(?array $recentMatches): self
    {
        $this->recentMatches = $recentMatches;

        return $this;
    }
}
