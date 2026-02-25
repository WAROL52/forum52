<?php

namespace App\Service;

class VerificationCodeGenerator
{
    private const CODE_LENGTH = 6;
    private const CODE_EXPIRATION_MINUTES = 15;

    public function generate(): string
    {
        // Generate a secure random 6-digit code
        return str_pad((string) random_int(0, 999999), self::CODE_LENGTH, '0', STR_PAD_LEFT);
    }

    public function getExpirationTime(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('+' . self::CODE_EXPIRATION_MINUTES . ' minutes');
    }

    public function getExpirationMinutes(): int
    {
        return self::CODE_EXPIRATION_MINUTES;
    }
}
