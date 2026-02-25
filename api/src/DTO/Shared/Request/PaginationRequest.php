<?php

namespace App\DTO\Shared\Request;

use Symfony\Component\Validator\Constraints as Assert;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'PaginationRequest',
    title: 'Pagination Request',
    description: 'Pagination query parameters',
    type: 'object'
)]
class PaginationRequest
{
    #[Assert\Type('integer')]
    #[Assert\Positive]
    #[OA\Property(description: 'Page number', example: 1, default: 1)]
    public int $page = 1;

    #[Assert\Type('integer')]
    #[Assert\Positive]
    #[Assert\LessThanOrEqual(100, message: 'Maximum limit is 100')]
    #[OA\Property(description: 'Items per page (max 100)', example: 10, default: 10)]
    public int $limit = 10;
}
