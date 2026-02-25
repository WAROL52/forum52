<?php

namespace App\Attribute;

use Attribute;

/**
 * Marks a field as filterable with specific operators
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
class FilterableField
{
    /**
     * @param string $path Field path (can be nested like "author.firstName")
     * @param string[] $operators Allowed operators for this field
     * @param string $type Field type (string, int, date, relation)
     */
    public function __construct(
        public string $path,
        public array $operators = [],
        public string $type = 'string'
    ) {
    }

    public function supportsOperator(string $operator): bool
    {
        return in_array($operator, $this->operators, true);
    }
}
