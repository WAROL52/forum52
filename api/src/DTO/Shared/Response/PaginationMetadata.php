<?php

namespace App\DTO\Shared\Response;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'PaginationMetadata',
    title: 'Pagination Metadata',
    description: 'Pagination information',
    required: ['total', 'page', 'limit', 'pages'],
    type: 'object'
)]
class PaginationMetadata
{
    #[OA\Property(description: 'Total number of items', example: 100)]
    public int $total;

    #[OA\Property(description: 'Current page number', example: 1)]
    public int $page;

    #[OA\Property(description: 'Items per page', example: 10)]
    public int $limit;

    #[OA\Property(description: 'Total number of pages', example: 10)]
    public int $pages;

    public static function fromArray(array $data): self
    {
        $metadata = new self();
        $metadata->total = $data['total'];
        $metadata->page = $data['page'];
        $metadata->limit = $data['limit'];
        $metadata->pages = $data['pages'];

        return $metadata;
    }
}
