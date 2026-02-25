<?php

namespace App\DTO\Auth\Response;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'VerificationSentResponse',
    title: 'Verification Sent Response',
    description: 'Response after verification code is sent',
    required: ['code', 'data'],
    type: 'object'
)]
class VerificationSentResponse
{
    #[OA\Property(description: 'Response code', example: 'VERIFICATION_CODE_SENT')]
    public string $code;

    #[OA\Property(
        description: 'Response data',
        type: 'object',
        properties: [
            new OA\Property(property: 'email', type: 'string', example: 'user@example.com')
        ]
    )]
    public object $data;

    public function __construct(string $code, string $email)
    {
        $this->code = $code;
        $this->data = (object)['email' => $email];
    }
}
