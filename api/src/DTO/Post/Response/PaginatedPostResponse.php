<?php

namespace App\DTO\Post\Response;

use App\DTO\Shared\Response\PaginationMetadata;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'PaginatedPostResponse',
    title: 'Paginated Post Response',
    description: 'Paginated list of posts',
    required: ['data', 'pagination'],
    type: 'object'
)]
class PaginatedPostResponse
{
    #[OA\Property(
        property: 'data',
        description: 'Array of posts',
        type: 'array',
        items: new OA\Items(ref: new Model(type: PostResponse::class))
    )]
    public array $data;

    #[OA\Property(
        property: 'pagination',
        description: 'Pagination metadata',
        ref: new Model(type: PaginationMetadata::class)
    )]
    public PaginationMetadata $pagination;
}
