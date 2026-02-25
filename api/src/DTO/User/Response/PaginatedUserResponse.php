<?php

namespace App\DTO\User\Response;

use App\DTO\Shared\Response\PaginationMetadata;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'PaginatedUserResponse',
    title: 'Paginated User Response',
    description: 'Paginated list of users',
    required: ['data', 'pagination'],
    type: 'object'
)]
class PaginatedUserResponse
{
    #[OA\Property(
        property: 'data',
        description: 'Array of users',
        type: 'array',
        items: new OA\Items(ref: new Model(type: UserResponse::class))
    )]
    public array $data;

    #[OA\Property(
        property: 'pagination',
        description: 'Pagination metadata',
        ref: new Model(type: PaginationMetadata::class)
    )]
    public PaginationMetadata $pagination;
}
