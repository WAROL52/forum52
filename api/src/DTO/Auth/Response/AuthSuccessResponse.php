<?php

namespace App\DTO\Auth\Response;

use App\DTO\User\Response\UserResponse;
use App\Entity\User;
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Attribute\Model;

#[OA\Schema(
    schema: 'AuthSuccessResponse',
    title: 'Authentication Success Response',
    description: 'Response after successful login or registration',
    required: ['code', 'data'],
    type: 'object'
)]
class AuthSuccessResponse
{
    #[OA\Property(description: 'Response code', example: 'REGISTER_SUCCESS')]
    public string $code;

    #[OA\Property(
        description: 'Response data',
        properties: [
            new OA\Property(property: 'user', ref: new Model(type: UserResponse::class)),
            new OA\Property(property: 'token', type: 'string', example: 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...'),
            new OA\Property(property: 'expiresAt', type: 'integer', example: 1736520600, description: 'JWT expiration timestamp (Unix timestamp in seconds)')
        ],
        type: 'object'
    )]
    public object $data;

    public function __construct(string $code, object $data)
    {
        $this->code = $code;
        $this->data = $data;
    }

    public static function create(string $code, User $user, string $token, int $expiresAt): self
    {
        return new self(
            $code,
            (object) [
                'user' => UserResponse::fromEntity($user),
                'token' => $token,
                'expiresAt' => $expiresAt
            ]
        );
    }
}
