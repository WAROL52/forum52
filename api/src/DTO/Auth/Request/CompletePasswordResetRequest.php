<?php

namespace App\DTO\Auth\Request;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

#[OA\Schema(
    schema: 'CompletePasswordResetRequest',
    title: 'Complete Password Reset',
    description: 'Complete password reset with verification code and new password',
    required: ['email', 'code', 'newPassword'],
    type: 'object'
)]
class CompletePasswordResetRequest
{
    #[Assert\NotBlank(message: 'Email is required')]
    #[Assert\Email(message: 'Invalid email format')]
    #[OA\Property(description: 'User email address', example: 'user@example.com')]
    public string $email;

    #[Assert\NotBlank(message: 'Verification code is required')]
    #[Assert\Length(exactly: 6, exactMessage: 'Verification code must be 6 characters')]
    #[OA\Property(description: 'Verification code received via email', example: '123456')]
    public string $code;

    #[Assert\NotBlank(message: 'New password is required')]
    #[Assert\Length(min: 8, minMessage: 'Password must be at least 8 characters')]
    #[OA\Property(description: 'New password', example: 'NewSecureP@ss123')]
    public string $newPassword;
}
