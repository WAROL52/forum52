<?php

namespace App\DTO\Post\Response;

use App\DTO\User\Response\UserResponse;
use App\Entity\Post;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'PostResponse',
    title: 'Post Response',
    description: 'Post data response with author details',
    required: ['id', 'title', 'content', 'author', 'createdAt'],
    type: 'object'
)]
class PostResponse
{
    #[OA\Property(description: 'Post ID', example: 1)]
    public int $id;

    #[OA\Property(description: 'Post title', example: 'My First Post')]
    public string $title;

    #[OA\Property(description: 'Post content', example: 'This is the content of my post')]
    public string $content;

    #[OA\Property(description: 'Post author', ref: '#/components/schemas/UserResponse')]
    public UserResponse $author;

    #[OA\Property(description: 'Creation date', example: '2024-01-15T10:30:00+00:00')]
    public string $createdAt;

    #[OA\Property(description: 'Last update date', example: '2024-01-16T14:20:00+00:00', nullable: true)]
    public ?string $updatedAt;

    public static function fromEntity(Post $post): self
    {
        $dto = new self();
        $dto->id = $post->getId();
        $dto->title = $post->getTitle();
        $dto->content = $post->getContent();
        $dto->author = UserResponse::fromEntity($post->getAuthor());
        $dto->createdAt = $post->getCreatedAt()->format(\DateTimeInterface::ATOM);
        $dto->updatedAt = $post->getUpdatedAt()?->format(\DateTimeInterface::ATOM);

        return $dto;
    }
}
