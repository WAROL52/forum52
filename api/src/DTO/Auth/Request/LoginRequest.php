<?php

namespace App\DTO\Auth\Request;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

#[OA\Schema(
    schema: 'LoginRequest',
    title: 'Login Request',
    description: 'Request body for user login',
    required: ['email', 'password'],
    type: 'object'
)]
class LoginRequest
{
    #[OA\Property(
        description: 'User email address',
        example: 'user@example.com'
    )]
    #[Assert\NotBlank(message: 'Email is required')]
    #[Assert\Email(message: 'Invalid email address')]
    public string $email;

    #[OA\Property(
        description: 'User password',
        example: 'SecureP@ssw0rd',
        format: 'password'
    )]
    #[Assert\NotBlank(message: 'Password is required')]
    public string $password;
}
