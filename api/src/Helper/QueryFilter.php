<?php

namespace App\Helper;

use App\Attribute\Filterable;
use App\Attribute\FilterableField;
use App\DTO\Shared\FilterCollection;
use App\DTO\Shared\FilterRequest;
use App\Exception\ApiException;
use Doctrine\ORM\QueryBuilder;
use ReflectionClass;
use Symfony\Component\HttpFoundation\Response;

class QueryFilter
{
    private int $parameterCounter = 0;

    /**
     * Apply filters to a QueryBuilder based on FilterCollection and entity class filterable fields
     *
     * @param QueryBuilder $queryBuilder
     * @param FilterCollection $filters
     * @param string $entityClass Fully qualified entity class name
     * @param string $alias Query alias (e.g., 'p' for Post)
     * @return QueryBuilder
     * @throws ApiException
     */
    public function applyFilters(
        QueryBuilder $queryBuilder,
        FilterCollection $filters,
        string $entityClass,
        string $alias
    ): QueryBuilder {
        if ($filters->isEmpty()) {
            return $queryBuilder;
        }

        // Get filterable fields configuration from entity
        $filterableFields = $this->getFilterableFields($entityClass);

        foreach ($filters->getFilters() as $filter) {
            $this->applyFilter($queryBuilder, $filter, $filterableFields, $alias);
        }

        return $queryBuilder;
    }

    /**
     * Apply a single filter to the QueryBuilder
     */
    private function applyFilter(
        QueryBuilder $queryBuilder,
        FilterRequest $filter,
        array $filterableFields,
        string $alias
    ): void {
        // Validate field exists and is filterable
        if (!isset($filterableFields[$filter->field])) {
            throw new ApiException(
                'INVALID_FILTER_FIELD',
                Response::HTTP_BAD_REQUEST,
                ['field' => $filter->field, 'message' => sprintf('Field "%s" is not filterable', $filter->field)]
            );
        }

        $fieldConfig = $filterableFields[$filter->field];

        // Validate operator is allowed for this field
        if (!$fieldConfig->supportsOperator($filter->operator)) {
            throw new ApiException(
                'INVALID_FILTER_OPERATOR',
                Response::HTTP_BAD_REQUEST,
                [
                    'field' => $filter->field,
                    'operator' => $filter->operator,
                    'message' => sprintf(
                        'Operator "%s" is not allowed for field "%s". Allowed: %s',
                        $filter->operator,
                        $filter->field,
                        implode(', ', $fieldConfig->operators)
                    )
                ]
            );
        }

        // Build the query path (handle nested fields with joins)
        $queryPath = $this->buildQueryPath($queryBuilder, $fieldConfig->path, $alias);

        // Apply the filter based on operator
        $this->applyOperator($queryBuilder, $filter->operator, $queryPath, $filter->value, $fieldConfig->type);
    }

    /**
     * Build query path and handle joins for nested fields
     */
    private function buildQueryPath(QueryBuilder $queryBuilder, string $path, string $alias): string
    {
        $parts = explode('.', $path);

        // If no nesting, return simple path
        if (count($parts) === 1) {
            return $alias . '.' . $path;
        }

        // Handle nested path with joins
        $currentAlias = $alias;

        for ($i = 0; $i < count($parts) - 1; $i++) {
            $relation = $parts[$i];
            $joinAlias = $relation . '_filter';

            // Check if join already exists
            $existingJoins = $queryBuilder->getDQLPart('join');
            $joinExists = false;

            if (isset($existingJoins[$currentAlias])) {
                foreach ($existingJoins[$currentAlias] as $join) {
                    if ($join->getAlias() === $joinAlias) {
                        $joinExists = true;
                        break;
                    }
                }
            }

            // Add join if it doesn't exist
            if (!$joinExists) {
                $queryBuilder->leftJoin($currentAlias . '.' . $relation, $joinAlias);
            }

            $currentAlias = $joinAlias;
        }

        // Return the final field path
        return $currentAlias . '.' . $parts[count($parts) - 1];
    }

    /**
     * Apply the operator to the query
     */
    private function applyOperator(
        QueryBuilder $queryBuilder,
        string $operator,
        string $queryPath,
        string $value,
        string $type
    ): void {
        $paramName = 'filter_param_' . $this->parameterCounter++;

        switch ($operator) {
            case 'eq':
                $queryBuilder->andWhere($queryPath . ' = :' . $paramName);
                $queryBuilder->setParameter($paramName, $this->castValue($value, $type));
                break;

            case 'ne':
                $queryBuilder->andWhere($queryPath . ' != :' . $paramName);
                $queryBuilder->setParameter($paramName, $this->castValue($value, $type));
                break;

            case 'gt':
                $queryBuilder->andWhere($queryPath . ' > :' . $paramName);
                $queryBuilder->setParameter($paramName, $this->castValue($value, $type));
                break;

            case 'gte':
                $queryBuilder->andWhere($queryPath . ' >= :' . $paramName);
                $queryBuilder->setParameter($paramName, $this->castValue($value, $type));
                break;

            case 'lt':
                $queryBuilder->andWhere($queryPath . ' < :' . $paramName);
                $queryBuilder->setParameter($paramName, $this->castValue($value, $type));
                break;

            case 'lte':
                $queryBuilder->andWhere($queryPath . ' <= :' . $paramName);
                $queryBuilder->setParameter($paramName, $this->castValue($value, $type));
                break;

            case 'in':
                $values = array_map('trim', explode(',', $value));
                $values = array_map(fn($v) => $this->castValue($v, $type), $values);
                $queryBuilder->andWhere($queryPath . ' IN (:' . $paramName . ')');
                $queryBuilder->setParameter($paramName, $values);
                break;

            case 'not_in':
                $values = array_map('trim', explode(',', $value));
                $values = array_map(fn($v) => $this->castValue($v, $type), $values);
                $queryBuilder->andWhere($queryPath . ' NOT IN (:' . $paramName . ')');
                $queryBuilder->setParameter($paramName, $values);
                break;

            case 'like':
                $queryBuilder->andWhere($queryPath . ' LIKE :' . $paramName);
                $queryBuilder->setParameter($paramName, '%' . $value . '%');
                break;

            case 'starts_with':
                $queryBuilder->andWhere($queryPath . ' LIKE :' . $paramName);
                $queryBuilder->setParameter($paramName, $value . '%');
                break;

            case 'ends_with':
                $queryBuilder->andWhere($queryPath . ' LIKE :' . $paramName);
                $queryBuilder->setParameter($paramName, '%' . $value);
                break;

            default:
                throw new ApiException(
                    'INVALID_FILTER_OPERATOR',
                    Response::HTTP_BAD_REQUEST,
                    ['operator' => $operator, 'message' => 'Unknown operator']
                );
        }
    }

    /**
     * Cast value to appropriate type
     */
    private function castValue(string $value, string $type): mixed
    {
        return match ($type) {
            'int' => (int) $value,
            'date' => new \DateTimeImmutable($value),
            'bool' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            default => $value,
        };
    }

    /**
     * Get filterable fields configuration from entity using reflection
     *
     * @return FilterableField[] Associative array with field path as key
     */
    private function getFilterableFields(string $entityClass): array
    {
        $reflection = new ReflectionClass($entityClass);

        // Check if entity is marked as filterable
        $filterableAttributes = $reflection->getAttributes(Filterable::class);
        if (empty($filterableAttributes)) {
            throw new ApiException(
                'ENTITY_NOT_FILTERABLE',
                Response::HTTP_BAD_REQUEST,
                ['message' => 'This entity does not support filtering']
            );
        }

        $fields = [];

        // Collect filterable fields from properties
        foreach ($reflection->getProperties() as $property) {
            $attributes = $property->getAttributes(FilterableField::class);
            foreach ($attributes as $attribute) {
                /** @var FilterableField $fieldConfig */
                $fieldConfig = $attribute->newInstance();
                $fields[$fieldConfig->path] = $fieldConfig;
            }
        }

        return $fields;
    }
}
