<?php

namespace App\Entity;

use App\Attribute\Filterable;
use App\Attribute\FilterableField;
use App\Repository\PostRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: PostRepository::class)]
#[Filterable]
class Post
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['post:read'])]
    #[FilterableField(path: 'id', operators: ['eq', 'in', 'not_in', 'ne'], type: 'int')]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['post:read', 'post:write'])]
    #[FilterableField(path: 'title', operators: ['eq', 'ne', 'in', 'not_in', 'like', 'starts_with', 'ends_with'], type: 'string')]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(['post:read', 'post:write'])]
    #[FilterableField(path: 'content', operators: ['eq', 'ne', 'like', 'starts_with', 'ends_with'], type: 'string')]
    private ?string $content = null;

    #[ORM\ManyToOne(inversedBy: 'posts')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['post:read'])]
    #[FilterableField(path: 'author.id', operators: ['eq', 'in', 'not_in', 'ne'], type: 'int')]
    #[FilterableField(path: 'author.firstName', operators: ['eq', 'ne', 'in', 'not_in', 'like', 'starts_with', 'ends_with'], type: 'string')]
    #[FilterableField(path: 'author.lastName', operators: ['eq', 'ne', 'in', 'not_in', 'like', 'starts_with', 'ends_with'], type: 'string')]
    private ?User $author = null;

    #[ORM\Column]
    #[Groups(['post:read'])]
    #[FilterableField(path: 'createdAt', operators: ['eq', 'gt', 'gte', 'lt', 'lte', 'ne'], type: 'date')]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['post:read'])]
    #[FilterableField(path: 'updatedAt', operators: ['eq', 'gt', 'gte', 'lt', 'lte', 'ne'], type: 'date')]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;

        return $this;
    }

    public function getAuthor(): ?User
    {
        return $this->author;
    }

    public function setAuthor(?User $author): static
    {
        $this->author = $author;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }
}
