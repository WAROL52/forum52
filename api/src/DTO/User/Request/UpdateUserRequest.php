<?php

namespace App\DTO\User\Request;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

#[OA\Schema(
    schema: 'UpdateUserRequest',
    title: 'Update User Request',
    description: 'Request body for updating user information',
    required: ['firstName', 'lastName'],
    type: 'object'
)]
class UpdateUserRequest
{
    #[OA\Property(
        description: 'User first name',
        example: 'John',
        minLength: 2,
        maxLength: 255
    )]
    #[Assert\NotBlank(message: 'First name is required')]
    #[Assert\Length(
        min: 2,
        max: 255,
        minMessage: 'First name must be at least {{ limit }} characters long',
        maxMessage: 'First name cannot be longer than {{ limit }} characters'
    )]
    public string $firstName;

    #[OA\Property(
        description: 'User last name',
        example: 'Doe',
        minLength: 2,
        maxLength: 255
    )]
    #[Assert\NotBlank(message: 'Last name is required')]
    #[Assert\Length(
        min: 2,
        max: 255,
        minMessage: 'Last name must be at least {{ limit }} characters long',
        maxMessage: 'Last name cannot be longer than {{ limit }} characters'
    )]
    public string $lastName;
}
