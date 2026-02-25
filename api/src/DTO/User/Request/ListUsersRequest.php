<?php

namespace App\DTO\User\Request;

use App\DTO\Shared\Request\PaginationRequest;
use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

#[OA\Schema(
    schema: 'ListUsersRequest',
    title: 'List Users Request',
    description: 'Query parameters for listing users with optional filtering',
    type: 'object'
)]
class ListUsersRequest extends PaginationRequest
{
    #[Assert\Type('string')]
    #[OA\Property(
        description: 'JSON array of filters. Operators: eq, ne, gt, lt, gte, lte, in, not_in, like, starts_with, ends_with',
        example: '[{"field":"firstName","operator":"starts_with","value":"Jo"}]',
        nullable: true
    )]
    public ?string $filters = null;
}
