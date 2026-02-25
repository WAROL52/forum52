<?php

namespace App\DTO\Auth\Response;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'PasswordResetSuccessResponse',
    title: 'Password Reset Success Response',
    description: 'Response when password reset is successful',
    required: ['code'],
    type: 'object'
)]
class PasswordResetSuccessResponse
{
    #[OA\Property(description: 'Response code', example: 'PASSWORD_RESET_SUCCESS')]
    public string $code;

    public function __construct()
    {
        $this->code = 'PASSWORD_RESET_SUCCESS';
    }
}
