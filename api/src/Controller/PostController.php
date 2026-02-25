<?php

namespace App\Controller;

use App\DTO\Post\Request\ListPostsRequest;
use App\DTO\Post\Request\PostRequest;
use App\DTO\Shared\Response\PaginatedResponse;
use App\DTO\Post\Response\PaginatedPostResponse;
use App\DTO\Post\Response\PostResponse;
use App\Entity\Post;
use App\Entity\User;
use App\Security\Voter\PostVoter;
use App\Service\PostService;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/posts', name: 'api_posts_')]
class PostController extends BaseApiController
{
    public function __construct(
        private PostService $postService,
    ) {
    }

    #[Route('', name: 'list', methods: ['GET'])]
    #[OA\Get(
        path: '/posts',
        summary: 'Get all posts with pagination and filtering',
        tags: ['Posts'],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'List of posts',
                content: new OA\JsonContent(ref: new Model(type: PaginatedPostResponse::class))
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, ref: '#/components/responses/BadRequestError')
        ]
    )]
    public function list(#[MapQueryString] ListPostsRequest $listRequest): JsonResponse
    {
        $filters = $this->parseFilters($listRequest->filters);
        $result = $this->postService->list($listRequest->page, $listRequest->limit, $filters);

        $response = PaginatedResponse::create(
            $result['data'],
            $result['pagination'],
            fn($post) => PostResponse::fromEntity($post)
        );

        return $this->jsonResponse($response);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    #[OA\Get(
        path: '/posts/{id}',
        summary: 'Get a post by ID',
        tags: ['Posts'],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Post found',
                content: new OA\JsonContent(ref: new Model(type: PostResponse::class))
            ),
            new OA\Response(response: Response::HTTP_NOT_FOUND, ref: '#/components/responses/NotFoundError')
        ]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[IsGranted(PostVoter::VIEW, 'post')]
    public function show(Post $post): JsonResponse
    {
        $response = PostResponse::fromEntity($post);

        return $this->jsonResponse($response);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    #[OA\Post(
        path: '/posts',
        summary: 'Create a new post',
        tags: ['Posts'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: new Model(type: PostRequest::class))
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_CREATED,
                description: 'Post created',
                content: new OA\JsonContent(ref: new Model(type: PostResponse::class))
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, ref: '#/components/responses/BadRequestError')
        ]
    )]
    #[IsGranted('ROLE_USER')]
    public function create(Request $request, #[CurrentUser] User $currentUser): JsonResponse
    {
        $postRequest = $this->serializer->deserialize(
            $request->getContent(),
            PostRequest::class,
            'json'
        );

        $this->validateRequest($postRequest);

        $post = $this->postService->create($postRequest, $currentUser);
        $response = PostResponse::fromEntity($post);

        return $this->jsonResponse($response, Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    #[OA\Put(
        path: '/posts/{id}',
        summary: 'Update a post',
        tags: ['Posts'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: new Model(type: PostRequest::class))
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Post updated',
                content: new OA\JsonContent(ref: new Model(type: PostResponse::class))
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, ref: '#/components/responses/BadRequestError'),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Not authorized to update this post'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, ref: '#/components/responses/NotFoundError')
        ]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[IsGranted(PostVoter::EDIT, 'post')]
    public function update(Post $post, Request $request): JsonResponse
    {
        $postRequest = $this->serializer->deserialize(
            $request->getContent(),
            PostRequest::class,
            'json'
        );

        $this->validateRequest($postRequest);

        $post = $this->postService->update($post, $postRequest);
        $response = PostResponse::fromEntity($post);

        return $this->jsonResponse($response);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    #[OA\Delete(
        path: '/posts/{id}',
        summary: 'Delete a post',
        tags: ['Posts'],
        responses: [
            new OA\Response(response: Response::HTTP_NO_CONTENT, description: 'Post deleted'),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Not authorized to delete this post'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, ref: '#/components/responses/NotFoundError')
        ]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[IsGranted(PostVoter::DELETE, 'post')]
    public function delete(Post $post): JsonResponse
    {
        $this->postService->delete($post);

        return $this->jsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
