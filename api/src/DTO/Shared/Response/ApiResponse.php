<?php

namespace App\DTO\Shared\Response;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ApiResponse',
    title: 'Standard API Response',
    description: 'Standard response format for all API endpoints',
    required: ['code'],
    type: 'object'
)]
class ApiResponse
{
    #[OA\Property(description: 'Response code', example: 'REGISTER_SUCCESS')]
    public string $code;

    #[OA\Property(
        description: 'Response data',
        type: 'object',
        nullable: true,
        example: ['user' => ['id' => 1, 'email' => 'user@example.com'], 'token' => 'eyJ0eXAi...']
    )]
    public mixed $data;

    public function __construct(string $code, mixed $data = null)
    {
        $this->code = $code;
        $this->data = $data;
    }
}
