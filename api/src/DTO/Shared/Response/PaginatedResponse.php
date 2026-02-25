<?php

namespace App\DTO\Shared\Response;

use OpenApi\Attributes as OA;

/**
 * @template T
 */
#[OA\Schema(
    schema: 'PaginatedResponse',
    title: 'Paginated Response',
    description: 'Generic paginated response wrapper',
    required: ['data', 'pagination'],
    type: 'object'
)]
class PaginatedResponse
{
    /**
     * @var T[]
     */
    #[OA\Property(
        description: 'Array of items',
        type: 'array',
        items: new OA\Items(type: 'object')
    )]
    public array $data;

    #[OA\Property(description: 'Pagination metadata')]
    public PaginationMetadata $pagination;

    /**
     * Create a paginated response from items and pagination data.
     *
     * @param array $items Raw items (entities) to transform
     * @param array $paginationData Pagination metadata array
     * @param callable $transformer Function to transform each item (e.g., UserResponse::fromEntity(...))
     * @return self<T>
     */
    public static function create(array $items, array $paginationData, callable $transformer): self
    {
        $response = new self();
        $response->data = array_map($transformer, $items);
        $response->pagination = PaginationMetadata::fromArray($paginationData);

        return $response;
    }
}
