<?php

namespace App\Exception;

use Exception;
use Symfony\Component\HttpFoundation\Response;

class ApiException extends Exception
{
    private array $violations = [];
    private string $responseCode;

    public function __construct(
        string $responseCode,
        int $httpCode = Response::HTTP_BAD_REQUEST,
        array $violations = [],
        ?\Throwable $previous = null
    ) {
        parent::__construct($responseCode, $httpCode, $previous);
        $this->responseCode = $responseCode;
        $this->violations = $violations;
    }

    public function getResponseCode(): string
    {
        return $this->responseCode;
    }

    public function getViolations(): array
    {
        return $this->violations;
    }

    public function hasViolations(): bool
    {
        return !empty($this->violations);
    }
}
