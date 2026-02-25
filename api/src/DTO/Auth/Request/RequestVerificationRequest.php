<?php

namespace App\DTO\Auth\Request;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

#[OA\Schema(
    schema: 'RequestVerificationRequest',
    title: 'Request Verification',
    description: 'Request to send verification code to email',
    required: ['email'],
    type: 'object'
)]
class RequestVerificationRequest
{
    #[Assert\NotBlank(message: 'Email is required')]
    #[Assert\Email(message: 'Invalid email format')]
    #[OA\Property(description: 'User email address', example: 'user@example.com')]
    public string $email;
}
