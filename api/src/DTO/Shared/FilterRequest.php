<?php

namespace App\DTO\Shared;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

#[OA\Schema(
    schema: 'FilterRequest',
    title: 'Filter Request',
    description: 'Individual filter criteria',
    required: ['field', 'operator', 'value'],
    type: 'object'
)]
class FilterRequest
{
    public const ALLOWED_OPERATORS = ['eq', 'ne', 'gt', 'lt', 'gte', 'lte', 'in', 'not_in', 'like', 'starts_with', 'ends_with'];

    #[OA\Property(
        description: 'Field to filter on (supports nested fields like "author.firstName")',
        example: 'title'
    )]
    #[Assert\NotBlank(message: 'Filter field is required')]
    #[Assert\Type('string')]
    public string $field;

    #[OA\Property(
        description: 'Filter operator',
        enum: ['eq', 'ne', 'gt', 'lt', 'gte', 'lte', 'in', 'not_in', 'like', 'starts_with', 'ends_with'],
        example: 'eq'
    )]
    #[Assert\NotBlank(message: 'Filter operator is required')]
    #[Assert\Choice(
        choices: self::ALLOWED_OPERATORS,
        message: 'Invalid operator. Allowed: {{ choices }}'
    )]
    public string $operator;

    #[OA\Property(
        description: 'Filter value (comma-separated for "in" and "not_in" operators)',
        example: 'John'
    )]
    #[Assert\NotBlank(message: 'Filter value is required')]
    public string $value;
}
