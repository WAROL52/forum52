<?php

namespace App\Controller;

use App\DTO\User\Request\ListUsersRequest;
use App\DTO\User\Request\UpdateUserRequest;
use App\DTO\Shared\Response\PaginatedResponse;
use App\DTO\User\Response\PaginatedUserResponse;
use App\DTO\User\Response\UserResponse;
use App\Entity\User;
use App\Security\Voter\UserVoter;
use App\Service\UserService;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/users', name: 'api_users_')]
#[IsGranted('ROLE_USER')]
class UserController extends BaseApiController
{
    public function __construct(
        private UserService $userService,
    ) {
    }

    #[Route('', name: 'list', methods: ['GET'])]
    #[OA\Get(
        path: '/users',
        summary: 'Get all users with pagination and filtering',
        tags: ['Users'],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'List of users',
                content: new OA\JsonContent(ref: new Model(type: PaginatedUserResponse::class))
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, ref: '#/components/responses/BadRequestError')
        ]
    )]
    public function list(#[MapQueryString] ListUsersRequest $listRequest): JsonResponse
    {
        $filters = $this->parseFilters($listRequest->filters);
        $result = $this->userService->list($listRequest->page, $listRequest->limit, $filters);

        $response = PaginatedResponse::create(
            $result['data'],
            $result['pagination'],
            fn($user) => UserResponse::fromEntity($user)
        );

        return $this->jsonResponse($response);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    #[OA\Get(
        path: '/users/{id}',
        summary: 'Get a user by ID',
        tags: ['Users'],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'User found',
                content: new OA\JsonContent(ref: new Model(type: UserResponse::class))
            ),
            new OA\Response(response: Response::HTTP_NOT_FOUND, ref: '#/components/responses/NotFoundError')
        ]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[IsGranted(UserVoter::VIEW, 'user')]
    public function show(User $user): JsonResponse
    {
        $response = UserResponse::fromEntity($user);

        return $this->jsonResponse($response);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    #[OA\Put(
        path: '/users/{id}',
        summary: 'Update a user',
        tags: ['Users'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: new Model(type: UpdateUserRequest::class))
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'User updated',
                content: new OA\JsonContent(ref: new Model(type: UserResponse::class))
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, ref: '#/components/responses/BadRequestError'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, ref: '#/components/responses/NotFoundError')
        ]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[IsGranted(UserVoter::EDIT, 'user')]
    public function update(User $user, Request $request): JsonResponse
    {
        $updateRequest = $this->serializer->deserialize(
            $request->getContent(),
            UpdateUserRequest::class,
            'json'
        );

        $this->validateRequest($updateRequest);

        $user = $this->userService->update($user, $updateRequest);
        $response = UserResponse::fromEntity($user);

        return $this->jsonResponse($response);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    #[OA\Delete(
        path: '/users/{id}',
        summary: 'Delete a user',
        tags: ['Users'],
        responses: [
            new OA\Response(response: Response::HTTP_NO_CONTENT, description: 'User deleted'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, ref: '#/components/responses/NotFoundError')
        ]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[IsGranted(UserVoter::DELETE, 'user')]
    public function delete(User $user): JsonResponse
    {
        $this->userService->delete($user);

        return $this->jsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
