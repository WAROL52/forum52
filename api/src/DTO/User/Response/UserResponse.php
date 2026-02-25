<?php

namespace App\DTO\User\Response;

use App\Entity\User;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'UserResponse',
    title: 'User Response',
    description: 'User data response',
    required: ['id', 'email', 'firstName', 'lastName', 'roles', 'createdAt'],
    type: 'object'
)]
class UserResponse
{
    #[OA\Property(description: 'User ID', example: 1)]
    public int $id;

    #[OA\Property(description: 'User email', example: 'user@example.com')]
    public string $email;

    #[OA\Property(description: 'User first name', example: 'John')]
    public string $firstName;

    #[OA\Property(description: 'User last name', example: 'Doe')]
    public string $lastName;

    #[OA\Property(description: 'User roles', type: 'array', items: new OA\Items(type: 'string'), example: ['ROLE_USER'])]
    public array $roles;

    #[OA\Property(description: 'Creation date', example: '2024-01-15T10:30:00+00:00')]
    public string $createdAt;

    public static function fromEntity(User $user): self
    {
        $dto = new self();
        $dto->id = $user->getId();
        $dto->email = $user->getEmail();
        $dto->firstName = $user->getFirstName();
        $dto->lastName = $user->getLastName();
        $dto->roles = $user->getRoles();
        $dto->createdAt = $user->getCreatedAt()->format(\DateTimeInterface::ATOM);

        return $dto;
    }
}
