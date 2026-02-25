<?php

namespace App\EventListener;

use App\Exception\ApiException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;

class ExceptionListener
{
    public function onKernelException(ExceptionEvent $event): void
    {
        $request = $event->getRequest();

        // Ne pas intercepter les routes de documentation Swagger/API Doc
        if (str_starts_with($request->getPathInfo(), '/doc')) {
            return;
        }

        $exception = $event->getThrowable();

        // Handle custom ApiException
        if ($exception instanceof ApiException) {
            $response = $this->handleApiException($exception);
            $event->setResponse($response);
            return;
        }

        // Handle ValidationFailedException
        if ($exception instanceof ValidationFailedException) {
            $response = $this->handleValidationException($exception);
            $event->setResponse($response);
            return;
        }

        // Handle HttpException (404, etc.)
        if ($exception instanceof HttpExceptionInterface) {
            $response = new JsonResponse([
                'message' => $exception->getMessage() ?: 'An error occurred',
            ], $exception->getStatusCode());
            $event->setResponse($response);
            return;
        }

        // Generic error
        $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR;
        $message = 'INTERNAL_SERVER_ERROR';

        // In dev mode, display the actual error message
        if ($_ENV['APP_ENV'] === 'dev') {
            $message = $exception->getMessage();
        }

        $response = new JsonResponse([
            'message' => $message,
        ], $statusCode);

        $event->setResponse($response);
    }

    private function handleApiException(ApiException $exception): JsonResponse
    {
        $data = [
            'message' => $exception->getResponseCode(),
        ];

        if ($exception->hasViolations()) {
            $data['errors'] = $exception->getViolations();
        }

        return new JsonResponse($data, $exception->getCode() ?: Response::HTTP_BAD_REQUEST);
    }

    private function handleValidationException(ValidationFailedException $exception): JsonResponse
    {
        $errors = [];
        foreach ($exception->getViolations() as $violation) {
            $errors[] = [
                'field' => $violation->getPropertyPath(),
                'message' => $violation->getMessage(),
            ];
        }

        return new JsonResponse([
            'message' => 'VALIDATION_FAILED',
            'errors' => $errors,
        ], Response::HTTP_BAD_REQUEST);
    }
}
