<?php

namespace App\DTO\Auth\Response;

use App\DTO\User\Response\UserResponse;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'AuthResponse',
    title: 'Authentication Response',
    description: 'Response containing user data and JWT token',
    required: ['user', 'token'],
    type: 'object'
)]
class AuthResponse
{
    #[OA\Property(description: 'User data', ref: '#/components/schemas/UserResponse')]
    public UserResponse $user;

    #[OA\Property(description: 'JWT authentication token', example: 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...')]
    public string $token;

    public function __construct(UserResponse $user, string $token)
    {
        $this->user = $user;
        $this->token = $token;
    }
}
