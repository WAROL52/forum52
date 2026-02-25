<?php

namespace App\DTO\Shared;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'FilterCollection',
    title: 'Filter Collection',
    description: 'Collection of filters to apply to a query',
    type: 'array',
    items: new OA\Items(ref: '#/components/schemas/FilterRequest')
)]
class FilterCollection
{
    /**
     * @var FilterRequest[]
     */
    private array $filters = [];

    /**
     * @param FilterRequest[] $filters
     */
    public function __construct(array $filters = [])
    {
        foreach ($filters as $filter) {
            if ($filter instanceof FilterRequest) {
                $this->filters[] = $filter;
            }
        }
    }

    /**
     * @return FilterRequest[]
     */
    public function getFilters(): array
    {
        return $this->filters;
    }

    public function isEmpty(): bool
    {
        return empty($this->filters);
    }

    public function count(): int
    {
        return count($this->filters);
    }

    /**
     * Create FilterCollection from array of filter data
     */
    public static function fromArray(array $data): self
    {
        $filters = [];
        foreach ($data as $filterData) {
            if (!is_array($filterData)) {
                continue;
            }

            $filter = new FilterRequest();
            $filter->field = $filterData['field'] ?? '';
            $filter->operator = $filterData['operator'] ?? '';
            $filter->value = $filterData['value'] ?? '';
            $filters[] = $filter;
        }

        return new self($filters);
    }
}
