<?php

namespace App\DTO\Auth\Request;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

#[OA\Schema(
    schema: 'RequestPasswordResetRequest',
    title: 'Request Password Reset',
    description: 'Request to send password reset code to email',
    required: ['email'],
    type: 'object'
)]
class RequestPasswordResetRequest
{
    #[Assert\NotBlank(message: 'Email is required')]
    #[Assert\Email(message: 'Invalid email format')]
    #[OA\Property(description: 'User email address', example: 'user@example.com')]
    public string $email;
}
