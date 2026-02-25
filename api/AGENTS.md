# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a Symfony 7.3 REST API with JWT authentication, built around email verification workflows and clean architecture patterns. The API features user and post management with comprehensive error handling and OpenAPI documentation.

**Key technologies**: PHP 8.2+, Symfony 7.3, Doctrine ORM, LexikJWTAuthenticationBundle, NelmioApiDocBundle

## Common Commands

### Database Operations
```bash
# Create database
php bin/console doctrine:database:create

# Generate and run migrations
php bin/console make:migration
php bin/console doctrine:migrations:migrate

# Drop database (careful!)
php bin/console doctrine:database:drop --force
```

### Development Server
```bash
# Start development server (Symfony CLI)
symfony serve

# Or using PHP built-in server
php -S localhost:8000 -t public/
```

### Testing
```bash
# Run all tests
php bin/phpunit

# Run specific test file
php bin/phpunit tests/Unit/Service/VerificationCodeGeneratorTest.php

# Run with coverage (if configured)
php bin/phpunit --coverage-html coverage/
```

### Dependencies
```bash
# Install dependencies
composer install

# Add new package
composer require package/name

# Clear cache
php bin/console cache:clear
```

## Architecture

### Request Flow
1. **Request** → Controller (minimal logic, extends `BaseApiController`)
2. **Deserialization** → DTO Request objects (in `src/DTO/*/Request/`)
3. **Validation** → Using Symfony Validator attributes on DTOs
4. **Service Layer** → Business logic (in `src/Service/`)
5. **Repository** → Database queries with Doctrine
6. **Response** → DTO Response objects (in `src/DTO/*/Response/`)
7. **Serialization** → JSON via Symfony Serializer

### Key Architectural Patterns

**BaseApiController**: All controllers extend `src/Controller/BaseApiController.php` which provides:
- `validateRequest(object $request)`: Validates DTOs and throws `ApiException` with field violations
- `jsonResponse(mixed $data, int $status)`: Serializes DTOs and returns JsonResponse

**ApiException**: Custom exception (`src/Exception/ApiException.php`) with:
- Response code (string, e.g., "EMAIL_ALREADY_EXISTS")
- HTTP status code
- Optional violations array for validation errors
- Caught globally by `ExceptionListener` and converted to structured JSON
- **IMPORTANT**: Error messages MUST always be in UPPERCASE_SNAKE_CASE format (e.g., "EMAIL_INVALID", "USER_NOT_FOUND", "INVALID_CREDENTIALS")

**ExceptionListener**: Global error handler (`src/EventListener/ExceptionListener.php`) that:
- Catches all exceptions and converts to JSON responses
- Handles `ApiException`, `ValidationFailedException`, and `HttpExceptionInterface`
- Skips `/doc` routes to allow Swagger UI to work
- In dev mode, exposes actual exception messages

**Pagination Helper**: Generic pagination (`src/Helper/Paginate.php`) used by repositories:
- Takes a Doctrine Query and returns `['data' => [...], 'pagination' => [...]]`
- Used consistently across User and Post listings

**QueryFilter Helper**: Generic filtering system (`src/Helper/QueryFilter.php`):
- Takes QueryBuilder + FilterCollection + Entity class + alias
- Applies filters dynamically based on field configuration
- Supports 11 operators: eq, ne, gt, lt, gte, lte, in, not_in, like, starts_with, ends_with
- Handles nested fields with automatic JOIN creation (e.g., `author.firstName`)
- Validates fields and operators against entity configuration
- Type-safe value casting (string, int, date, bool)

### Generic Filtering System

The project implements a reusable, attribute-based filtering system for all list endpoints.

**Architecture components:**

1. **Filter DTOs** (`src/DTO/Shared/`):
   - `FilterRequest`: Represents a single filter (field, operator, value)
   - `FilterCollection`: Wrapper for multiple filters with validation

2. **Attributes** (`src/Attribute/`):
   - `#[Filterable]`: Marks an entity as filterable (class-level)
   - `#[FilterableField]`: Defines filterable fields with allowed operators (property-level)

3. **QueryFilter Helper** (`src/Helper/QueryFilter.php`):
   - `applyFilters()`: Main method to apply filters to QueryBuilder
   - Validates field existence and operator compatibility
   - Handles nested fields with automatic JOINs
   - Type-safe value casting

4. **BaseApiController** (`src/Controller/BaseApiController.php`):
   - `parseFilters(?string $filtersJson)`: Parses and validates JSON filters
   - Returns FilterCollection ready to use

**Supported operators:**
- `eq`, `ne`: Equality (any type)
- `gt`, `gte`, `lt`, `lte`: Comparisons (numbers, dates)
- `in`, `not_in`: List membership (comma-separated values)
- `like`, `starts_with`, `ends_with`: Text search (strings only)

**Example entity configuration:**
```php
use App\Attribute\Filterable;
use App\Attribute\FilterableField;

#[Filterable]
class Post {
    #[FilterableField(path: 'title', operators: ['eq', 'like', 'starts_with'], type: 'string')]
    private string $title;

    #[FilterableField(path: 'author.firstName', operators: ['eq', 'like'], type: 'string')]
    private User $author;

    #[FilterableField(path: 'createdAt', operators: ['gt', 'gte', 'lt', 'lte'], type: 'date')]
    private \DateTimeImmutable $createdAt;
}
```

**Example controller usage:**
```php
public function list(#[MapQueryString] ListPostsRequest $listRequest): JsonResponse
{
    $filters = $this->parseFilters($listRequest->filters); // Inherited from BaseApiController
    $result = $this->postService->list($listRequest->page, $listRequest->limit, $filters);
    // ...
}
```

**Example repository usage:**
```php
public function createFindAllQuery(FilterCollection $filters): Query
{
    $qb = $this->createQueryBuilder('p')
        ->orderBy('p.createdAt', 'DESC');

    $this->queryFilter->applyFilters($qb, $filters, Post::class, 'p');

    return $qb->getQuery();
}
```

**API request example:**
```bash
GET /posts?filters=[{"field":"title","operator":"like","value":"API"},{"field":"author.firstName","operator":"eq","value":"John"}]
```

**Error codes:**
- `INVALID_FILTER_FORMAT`: JSON parsing failed
- `INVALID_FILTER_FIELD`: Field not filterable
- `INVALID_FILTER_OPERATOR`: Operator not allowed for field
- `ENTITY_NOT_FILTERABLE`: Entity missing #[Filterable] attribute

### Email Verification Flow

The app uses a two-step verification process for registration and password reset:

1. **Request Verification** (`/register/request`):
   - Creates `PendingVerification` entity with 6-digit code
   - Sends verification email via `MailService`
   - Code expires after configured minutes

2. **Complete Registration/Reset** (`/register/complete`, `/password-reset/complete`):
   - Validates verification code against `PendingVerification`
   - Creates User entity or updates password
   - Deletes `PendingVerification` record
   - Sends welcome/confirmation email

**PendingVerification Entity**: Stores temporary verification codes with:
- `email`: Target email
- `code`: Generated verification code
- `type`: Either registration or password reset
- `expiresAt`: Expiration timestamp

### Security Configuration

JWT authentication is configured in `config/packages/security.yaml`:
- **Public routes**: `/doc`, `/login`, `/register`, `/password-reset` (no JWT required)
- **Protected routes**: All other routes require `IS_AUTHENTICATED_FULLY`
- **Firewalls**: Separate firewall for login/register (no JWT) and API (JWT required)

**User Provider**: Uses email as the identifier (configured in security.yaml)

### Response Conversion Pattern

The project uses **Response DTOs** with static `fromEntity()` methods to convert entities to API responses. Serialization groups exist in entities for legacy/compatibility reasons but are **not actively used** in controllers.

**Pattern**: Entity → `ResponseDTO::fromEntity($entity)` → JSON

### Project Structure

```
src/
├── Attribute/
│   ├── Filterable.php           # Marks entity as filterable
│   └── FilterableField.php      # Defines filterable field config
├── Controller/
│   ├── BaseApiController.php    # validateRequest(), jsonResponse(), parseFilters()
│   ├── AuthController.php
│   ├── UserController.php
│   └── PostController.php
├── DTO/
│   ├── Shared/
│   │   ├── FilterRequest.php    # Single filter (field, operator, value)
│   │   ├── FilterCollection.php # Collection of filters
│   │   ├── Request/             # PaginationRequest
│   │   └── Response/            # PaginatedResponse
│   ├── Auth/Request/ & Response/
│   ├── User/Request/ & Response/
│   └── Post/Request/ & Response/
├── Entity/
│   ├── User.php                 # #[Filterable] + #[FilterableField] attributes
│   ├── Post.php                 # #[Filterable] + #[FilterableField] attributes
│   └── PendingVerification.php
├── Repository/
│   ├── UserRepository.php       # Uses QueryFilter
│   ├── PostRepository.php       # Uses QueryFilter
│   └── PendingVerificationRepository.php
├── Service/
│   ├── UserService.php          # Accepts FilterCollection
│   ├── PostService.php          # Accepts FilterCollection
│   ├── MailService.php
│   └── VerificationCodeGenerator.php
├── Helper/
│   ├── Paginate.php             # Generic pagination
│   └── QueryFilter.php          # Generic filtering (applyFilters)
├── Security/Voter/
├── Exception/ApiException.php
└── EventListener/ExceptionListener.php
```

### DTO Structure

DTOs are organized in namespaces:
- `App\DTO\Shared\*`: Shared DTOs (FilterRequest, FilterCollection, pagination, API responses)
- `App\DTO\Auth\Request\*`: Authentication requests (login, register, password reset)
- `App\DTO\Auth\Response\*`: Authentication responses
- `App\DTO\User\Request\*`: User CRUD requests (includes ListUsersRequest with filters)
- `App\DTO\User\Response\*`: User responses (including paginated)
- `App\DTO\Post\Request\*`: Post CRUD requests (includes ListPostsRequest with filters)
- `App\DTO\Post\Response\*`: Post responses (including paginated)

### Error Response Format

All errors return JSON with consistent structure:

**Simple error (no field violations):**
```json
{
  "message": "INVALID_CREDENTIALS"
}
```

**Error with field violations:**
```json
{
  "message": "VALIDATION_ERROR",
  "errors": [
    {"field": "email", "message": "This value is not a valid email."},
    {"field": "firstName", "message": "This value should not be blank."}
  ]
}
```

The `errors` field is **optional** and only present when there are field validation errors.

**Error Code Convention**: Always use UPPERCASE_SNAKE_CASE for error codes:
- ✅ `EMAIL_ALREADY_EXISTS`
- ✅ `INVALID_CREDENTIALS`
- ✅ `USER_NOT_FOUND`
- ✅ `VERIFICATION_CODE_EXPIRED`
- ✅ `INVALID_VERIFICATION_CODE`
- ✅ `VALIDATION_ERROR`
- ❌ `Email already exists` (wrong - not a code)
- ❌ `invalid_credentials` (wrong - lowercase)
- ❌ `User not found` (wrong - human readable)

HTTP status codes are used appropriately (400 for validation, 401 for unauthorized, 404 for not found, etc.)

**OpenAPI Error Components**: Reusable error response components are defined in `config/packages/dev/nelmio_api_doc.yaml`:
- `ErrorResponse` schema: Flexible structure supporting both simple and validation errors
- `FieldError` schema: Individual field error structure
- `BadRequestError` response: 400 errors (validation or invalid data)
- `UnauthorizedError` response: 401 authentication errors
- `NotFoundError` response: 404 resource not found errors

Use these components in controller `#[OA\Response]` annotations:
```php
new OA\Response(response: Response::HTTP_BAD_REQUEST, ref: '#/components/responses/BadRequestError')
new OA\Response(response: Response::HTTP_UNAUTHORIZED, ref: '#/components/responses/UnauthorizedError')
new OA\Response(response: Response::HTTP_NOT_FOUND, ref: '#/components/responses/NotFoundError')
```

## Service Dependencies

Services use constructor injection. Key services:
- `EntityManagerInterface`: Doctrine ORM
- `UserPasswordHasherInterface`: Password hashing
- `Paginate`: Pagination helper
- `MailService`: Email sending via Mailgun (configured in services.yaml)
- `VerificationCodeGenerator`: Generates 6-digit codes with expiration

**MailService Configuration**: Requires environment variables:
- `MAILER_FROM_EMAIL`: Sender email
- `MAILER_FROM_NAME`: Sender name

## Testing

Tests are organized in:
- `tests/Unit/`: Unit tests (e.g., `VerificationCodeGeneratorTest.php`)
- `tests/Integration/`: Integration tests (e.g., `MailServiceTest.php`)
- `tests/Utils/`: Test utilities

**ApiTestCase**: Base test class for API tests providing:
- `request(method, uri, data, token)`: Make JSON API requests
- `getJsonResponse()`: Parse response as array
- `assertResponseStatusCode(expected)`: Assert HTTP status
- `assertJsonResponseHasKey(key)`: Assert JSON contains key
- `assertValidationError()`: Assert validation error response

**AuthenticationHelper**: Utility for obtaining JWT tokens in tests

**FixturesLoader**: Loads test fixtures (using DoctrineFixturesBundle)

## API Documentation

The API has comprehensive OpenAPI 3.0 documentation available at `/doc` when the server is running. Use this interactive documentation to:
- Explore all available endpoints
- Test API requests directly from the browser
- View request/response schemas and examples
- See authentication requirements for each endpoint

### Documentation Configuration

**Automatic documentation** (no manual updates needed):
- DTOs are autodocumented via `#[OA\Schema]` and `#[OA\Property]` attributes
- Controllers are autodocumented via `#[OA\Get]`, `#[OA\Post]`, etc. attributes
- When you modify DTOs or controllers, the documentation updates automatically

**Manual configuration** (`config/packages/dev/nelmio_api_doc.yaml`):
- Only edit this file to add/modify **reusable error response components**
- Global security schemes (JWT Bearer)
- API metadata (title, description, version)

**Example**: Adding a new DTO field only requires adding `#[OA\Property]` to the DTO - no YAML changes needed.

### API Versioning

A pre-commit Git hook (`.githooks/pre-commit`) automatically checks if:
- DTO files have been modified
- The API version in `config/packages/dev/nelmio_api_doc.yaml` has been updated accordingly

**Version increment guidelines:**
- **Major (1.0.0 → 2.0.0)**: Breaking changes (removing fields, changing types)
- **Minor (1.0.0 → 1.1.0)**: New features (new endpoints, new optional fields)
- **Patch (1.0.0 → 1.0.1)**: Bug fixes (typos, validation fixes)

To install the hook: `cp .githooks/pre-commit .git/hooks/pre-commit && chmod +x .git/hooks/pre-commit`

## Environment Configuration

Key environment variables (in `.env` or `.env.local`):
- `DATABASE_URL`: MySQL connection string
- `JWT_SECRET_KEY`: Path to JWT private key
- `JWT_PUBLIC_KEY`: Path to JWT public key
- `JWT_PASSPHRASE`: JWT key passphrase
- `MAILER_DSN`: Mailgun DSN
- `MAILER_FROM_EMAIL`: Sender email address
- `MAILER_FROM_NAME`: Sender name
- `APP_ENV`: Environment (dev, prod, test)

## Code Generation

Use Symfony Maker Bundle for scaffolding:
```bash
# Generate entity
php bin/console make:entity

# Generate migration
php bin/console make:migration

# Generate controller
php bin/console make:controller
```

## Code Examples

### Example: Complete Controller (PostController)

```php
<?php

namespace App\Controller;

use App\DTO\Post\Request\PostRequest;
use App\DTO\Post\Response\PostResponse;
use App\Entity\Post;
use App\Entity\User;
use App\Service\PostService;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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

    // CREATE endpoint
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
        // 1. Deserialize request body into DTO
        $postRequest = $this->serializer->deserialize(
            $request->getContent(),
            PostRequest::class,
            'json'
        );

        // 2. Validate DTO (throws ApiException if invalid)
        $this->validateRequest($postRequest);

        // 3. Call service for business logic
        $post = $this->postService->create($postRequest, $currentUser);

        // 4. Convert entity to response DTO
        $response = PostResponse::fromEntity($post);

        // 5. Return JSON response
        return $this->jsonResponse($response, Response::HTTP_CREATED);
    }

    // UPDATE endpoint
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
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Not authorized'),
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

    // DELETE endpoint
    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    #[OA\Delete(
        path: '/posts/{id}',
        summary: 'Delete a post',
        tags: ['Posts'],
        responses: [
            new OA\Response(response: Response::HTTP_NO_CONTENT, description: 'Post deleted'),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Not authorized'),
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

    // GET LIST with query parameters
    #[Route('', name: 'list', methods: ['GET'])]
    #[OA\Get(
        path: '/posts',
        summary: 'Get all posts with pagination',
        tags: ['Posts'],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'List of posts',
                content: new OA\JsonContent(ref: new Model(type: PaginatedPostResponse::class))
            )
        ]
    )]
    public function list(#[MapQueryString] ListPostsRequest $listRequest): JsonResponse
    {
        $result = $this->postService->list($listRequest->page, $listRequest->limit, $listRequest->authorId);

        $response = PaginatedResponse::create(
            $result['data'],
            $result['pagination'],
            fn($post) => PostResponse::fromEntity($post)
        );

        return $this->jsonResponse($response);
    }

    // GET ONE by ID
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
}
```

### Example: Request DTO

```php
<?php

namespace App\DTO\Post\Request;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

#[OA\Schema(
    schema: 'PostRequest',
    title: 'Post Request',
    description: 'Request body for creating or updating a post',
    required: ['title', 'content'],
    type: 'object'
)]
class PostRequest
{
    #[OA\Property(
        description: 'Post title',
        example: 'My First Blog Post',
        minLength: 3,
        maxLength: 255
    )]
    #[Assert\NotBlank(message: 'Title is required')]
    #[Assert\Length(
        min: 3,
        max: 255,
        minMessage: 'Title must be at least {{ limit }} characters long',
        maxMessage: 'Title cannot be longer than {{ limit }} characters'
    )]
    public string $title;

    #[OA\Property(
        description: 'Post content',
        example: 'This is the content of my first blog post.',
        minLength: 10
    )]
    #[Assert\NotBlank(message: 'Content is required')]
    #[Assert\Length(
        min: 10,
        minMessage: 'Content must be at least {{ limit }} characters long'
    )]
    public string $content;
}
```

### Example: Response DTO

```php
<?php

namespace App\DTO\Post\Response;

use App\DTO\User\Response\UserResponse;
use App\Entity\Post;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'PostResponse',
    title: 'Post Response',
    description: 'Post data response with author details',
    required: ['id', 'title', 'content', 'author', 'createdAt'],
    type: 'object'
)]
class PostResponse
{
    #[OA\Property(description: 'Post ID', example: 1)]
    public int $id;

    #[OA\Property(description: 'Post title', example: 'My First Post')]
    public string $title;

    #[OA\Property(description: 'Post content', example: 'This is the content')]
    public string $content;

    #[OA\Property(description: 'Post author', ref: '#/components/schemas/UserResponse')]
    public UserResponse $author;

    #[OA\Property(description: 'Creation date', example: '2024-01-15T10:30:00+00:00')]
    public string $createdAt;

    #[OA\Property(description: 'Last update date', example: '2024-01-16T14:20:00+00:00', nullable: true)]
    public ?string $updatedAt;

    // Static factory method to convert Entity to DTO
    public static function fromEntity(Post $post): self
    {
        $dto = new self();
        $dto->id = $post->getId();
        $dto->title = $post->getTitle();
        $dto->content = $post->getContent();
        $dto->author = UserResponse::fromEntity($post->getAuthor());
        $dto->createdAt = $post->getCreatedAt()->format(\DateTimeInterface::ATOM);
        $dto->updatedAt = $post->getUpdatedAt()?->format(\DateTimeInterface::ATOM);

        return $dto;
    }
}
```

### Example: Service

```php
<?php

namespace App\Service;

use App\DTO\Post\Request\PostRequest;
use App\Entity\Post;
use App\Entity\User;
use App\Helper\Paginate;
use App\Repository\PostRepository;
use Doctrine\ORM\EntityManagerInterface;

class PostService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private PostRepository $postRepository,
        private Paginate $paginate,
    ) {
    }

    /**
     * Create a new post
     */
    public function create(PostRequest $request, User $author): Post
    {
        $post = new Post();
        $post->setTitle($request->title);
        $post->setContent($request->content);
        $post->setAuthor($author);

        $this->entityManager->persist($post);
        $this->entityManager->flush();

        return $post;
    }

    /**
     * Update a post
     */
    public function update(Post $post, PostRequest $request): Post
    {
        $post->setTitle($request->title);
        $post->setContent($request->content);
        $post->setUpdatedAt(new \DateTimeImmutable());

        $this->entityManager->flush();

        return $post;
    }

    /**
     * Delete a post
     */
    public function delete(Post $post): void
    {
        $this->entityManager->remove($post);
        $this->entityManager->flush();
    }

    /**
     * Get all posts with pagination
     */
    public function list(int $page = 1, int $limit = 10, ?int $authorId = null): array
    {
        $query = $this->postRepository->createFindAllQuery($authorId);
        $total = $this->postRepository->countAll($authorId);

        return $this->paginate->get($query, $page, $limit, $total);
    }
}
```

### Controller Attributes Reference

**Route Definition:**
```php
#[Route('/posts', name: 'api_posts_')]           // Base route for controller
#[Route('/{id}', name: 'show', methods: ['GET'])] // Method route
```

**Security:**
```php
#[IsGranted('ROLE_USER')]                        // Requires role
#[IsGranted(PostVoter::EDIT, 'post')]           // Requires voter permission
```

**Getting Current User:**
```php
public function create(Request $request, #[CurrentUser] User $currentUser)
```

**Query Parameters:**
```php
#[MapQueryString] ListPostsRequest $request  // Maps query string to DTO
```

**OpenAPI Documentation:**
```php
#[OA\Post(                                   // HTTP method
    path: '/posts',                          // API path
    summary: 'Create a new post',            // Short description
    tags: ['Posts'],                         // Group in Swagger UI
    requestBody: new OA\RequestBody(         // Request body schema
        required: true,
        content: new OA\JsonContent(ref: new Model(type: PostRequest::class))
    ),
    responses: [                             // Possible responses
        new OA\Response(
            response: Response::HTTP_CREATED,
            description: 'Post created',
            content: new OA\JsonContent(ref: new Model(type: PostResponse::class))
        )
    ]
)]
#[OA\Parameter(                              // Path/query parameters
    name: 'id',
    in: 'path',
    required: true,
    schema: new OA\Schema(type: 'integer')
)]
```

## When Adding Filtering to New Entities

To add filtering support to a new entity:

1. **Mark entity as filterable** - Add `#[Filterable]` attribute at class level
2. **Configure filterable fields** - Add `#[FilterableField]` attributes on properties:
   ```php
   #[FilterableField(
       path: 'fieldName',              // Field path (supports nested: 'author.name')
       operators: ['eq', 'like'],      // Allowed operators for this field
       type: 'string'                  // Field type: string, int, date, bool
   )]
   ```
3. **Update Request DTO** - Add `filters` property (copy from existing ListRequest DTOs)
4. **Update Repository** - Inject `QueryFilter` and call `applyFilters()` in query methods
5. **Update Service** - Accept `FilterCollection` parameter
6. **Update Controller** - Call `$this->parseFilters($listRequest->filters)`
7. **Update OpenAPI docs** - Use `#/components/parameters/FiltersParameter` reference

**Important**: Always use whitelist approach - only fields with `#[FilterableField]` are filterable.

## When Adding New Features

1. **Entity**: Create/update in `src/Entity/`
2. **Repository**: Add queries to repository (extends `ServiceEntityRepository`)
3. **DTOs**: Create request/response DTOs in `src/DTO/*/Request/` and `src/DTO/*/Response/`
   - Request DTOs: Use `Assert\*` attributes for validation
   - Response DTOs: Include `fromEntity()` static method
   - Both: Add `OA\Schema` and `OA\Property` attributes for OpenAPI docs
4. **Service**: Add business logic in `src/Service/`
   - Use constructor injection for dependencies
   - Return entities or arrays (not DTOs)
5. **Controller**: Create controller extending `BaseApiController`
   - Add `#[Route]` attribute at class level
   - Add route, security, and OpenAPI attributes to methods
   - Follow pattern: deserialize → validate → service → response DTO → json
6. **Routes**: Defined via attributes (no separate routing files needed)
7. **Validation**: Add Symfony Validator constraints to DTO properties
8. **Tests**: Write tests in `tests/` following existing patterns

**Always extend BaseApiController** for new controllers - it provides validation and JSON response helpers.

**Always use ApiException** for business logic errors - it's caught globally and converted to proper JSON.
```php
// Example: Throwing ApiException in a Service
throw new ApiException('EMAIL_ALREADY_EXISTS', Response::HTTP_BAD_REQUEST);
throw new ApiException('USER_NOT_FOUND', Response::HTTP_NOT_FOUND);
throw new ApiException('INVALID_CREDENTIALS', Response::HTTP_UNAUTHORIZED);
throw new ApiException('VERIFICATION_CODE_EXPIRED', Response::HTTP_BAD_REQUEST);
```

**Always use Services** for business logic - controllers should only handle HTTP concerns.

**Error messages must be codes**: Use UPPERCASE_SNAKE_CASE error codes (e.g., "EMAIL_INVALID"), never human-readable messages.
