<?php

namespace App\Controller;

use App\DTO\Shared\FilterCollection;
use App\Exception\ApiException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Contracts\Service\Attribute\Required;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

abstract class BaseApiController extends AbstractController
{
    protected SerializerInterface $serializer;
    protected ValidatorInterface $validator;

    #[Required]
    public function setSerializer(SerializerInterface $serializer): void
    {
        $this->serializer = $serializer;
    }

    #[Required]
    public function setValidator(ValidatorInterface $validator): void
    {
        $this->validator = $validator;
    }

    /**
     * Validates a request object and throws ApiException if validation fails
     *
     * @param object $request The request object to validate
     * @throws ApiException When validation fails
     */
    protected function validateRequest(object $request): void
    {
        $errors = $this->validator->validate($request);

        if (count($errors) > 0) {
            $violations = [];
            foreach ($errors as $error) {
                $violations[] = [
                    'field' => $error->getPropertyPath(),
                    'message' => $error->getMessage(),
                ];
            }
            throw new ApiException('VALIDATION_ERROR', Response::HTTP_BAD_REQUEST, $violations);
        }
    }

    /**
     * Parse and validate filters from JSON string
     *
     * @param string|null $filtersJson JSON string containing filters
     * @return FilterCollection Validated filter collection
     * @throws ApiException When JSON is invalid or filter validation fails
     */
    protected function parseFilters(?string $filtersJson): FilterCollection
    {
        if ($filtersJson === null) {
            return new FilterCollection();
        }

        try {
            $filtersData = json_decode($filtersJson, true, 512, JSON_THROW_ON_ERROR);
            $filters = FilterCollection::fromArray($filtersData);

            // Validate each filter
            foreach ($filters->getFilters() as $filter) {
                $this->validateRequest($filter);
            }

            return $filters;
        } catch (\JsonException) {
            throw new ApiException('INVALID_FILTER_FORMAT', Response::HTTP_BAD_REQUEST, [
                ['field' => 'filters', 'message' => 'Invalid JSON format for filters']
            ]);
        }
    }

    /**
     * Returns a JSON response
     *
     * @param mixed $data The data to return
     * @param int $status HTTP status code
     * @return JsonResponse
     */
    protected function jsonResponse(mixed $data, int $status = Response::HTTP_OK): JsonResponse
    {
        // If null (e.g., DELETE 204), return empty response
        if ($data === null) {
            return new JsonResponse(null, $status);
        }

        // If object (DTO), use serializer to convert it
        if (is_object($data)) {
            $json = $this->serializer->serialize($data, 'json');
            return new JsonResponse($json, $status, [], true);
        }

        // Otherwise (array or primitive), let JsonResponse handle it
        return new JsonResponse($data, $status);
    }
}
