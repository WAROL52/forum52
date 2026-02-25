<?php

namespace App\DTO\Post\Request;

use App\DTO\Shared\Request\PaginationRequest;
use Symfony\Component\Validator\Constraints as Assert;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ListPostsRequest',
    title: 'List Posts Request',
    description: 'Query parameters for listing posts with optional filtering',
    type: 'object'
)]
class ListPostsRequest extends PaginationRequest
{
    #[Assert\Type('string')]
    #[OA\Property(
        description: 'JSON array of filters. Operators: eq, ne, gt, lt, gte, lte, in, not_in, like, starts_with, ends_with',
        example: '[{"field":"title","operator":"like","value":"Post"}]',
        nullable: true
    )]
    public ?string $filters = null;
}
