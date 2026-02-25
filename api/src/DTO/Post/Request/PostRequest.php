<?php

namespace App\DTO\Post\Request;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

#[OA\Schema(
    schema: 'PostRequest',
    title: 'Post Request',
    description: 'Request body for creating or updating a post',
    required: ['title', 'content'],
    type: 'object'
)]
class PostRequest
{
    #[OA\Property(
        description: 'Post title',
        example: 'My First Blog Post',
        minLength: 3,
        maxLength: 255
    )]
    #[Assert\NotBlank(message: 'Title is required')]
    #[Assert\Length(
        min: 3,
        max: 255,
        minMessage: 'Title must be at least {{ limit }} characters long',
        maxMessage: 'Title cannot be longer than {{ limit }} characters'
    )]
    public string $title;

    #[OA\Property(
        description: 'Post content',
        example: 'This is the content of my first blog post. It contains useful information about various topics.',
        minLength: 10
    )]
    #[Assert\NotBlank(message: 'Content is required')]
    #[Assert\Length(
        min: 10,
        minMessage: 'Content must be at least {{ limit }} characters long'
    )]
    public string $content;
}
