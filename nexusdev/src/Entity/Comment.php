<?php

namespace App\Entity;

use App\Repository\CommentRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CommentRepository::class)]
#[ORM\Table(name: 'guide_comment')]
class Comment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'text')]
    private string $content;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'comments')]
    #[ORM\JoinColumn(name: 'user_id', nullable: false)]
    private User $author;

    #[ORM\ManyToOne(targetEntity: ForumPost::class, inversedBy: 'comments')]
    #[ORM\JoinColumn(nullable: true)]
    private ?ForumPost $post = null;

    #[ORM\ManyToOne(targetEntity: Content::class, inversedBy: 'comments')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Content $guide = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function getAuthor(): User
    {
        return $this->author;
    }

    public function setAuthor(User $author): self
    {
        $this->author = $author;

        return $this;
    }

    public function getPost(): ForumPost
    {
        return $this->post;
    }

    public function setPost(?ForumPost $post): self
    {
        $this->post = $post;

        return $this;
    }

    public function getGuide(): ?Content
    {
        return $this->guide;
    }

    public function setGuide(?Content $guide): self
    {
        $this->guide = $guide;

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
}
