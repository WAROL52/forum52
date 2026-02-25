<?php

namespace App\DTO\Auth\Request;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

#[OA\Schema(
    schema: 'CompleteRegistrationRequest',
    title: 'Complete Registration',
    description: 'Complete registration with verification code and user details',
    required: ['email', 'code', 'firstName', 'lastName', 'password'],
    type: 'object'
)]
class CompleteRegistrationRequest
{
    #[Assert\NotBlank(message: 'Email is required')]
    #[Assert\Email(message: 'Invalid email format')]
    #[OA\Property(description: 'User email address', example: 'user@example.com')]
    public string $email;

    #[Assert\NotBlank(message: 'Verification code is required')]
    #[Assert\Length(exactly: 6, exactMessage: 'Verification code must be 6 characters')]
    #[OA\Property(description: 'Verification code received via email', example: '123456')]
    public string $code;

    #[Assert\NotBlank(message: 'First name is required')]
    #[Assert\Length(min: 2, max: 255)]
    #[OA\Property(description: 'User first name', example: 'John')]
    public string $firstName;

    #[Assert\NotBlank(message: 'Last name is required')]
    #[Assert\Length(min: 2, max: 255)]
    #[OA\Property(description: 'User last name', example: 'Doe')]
    public string $lastName;

    #[Assert\NotBlank(message: 'Password is required')]
    #[Assert\Length(min: 8, minMessage: 'Password must be at least 8 characters')]
    #[OA\Property(description: 'User password', example: 'SecureP@ss123')]
    public string $password;
}
