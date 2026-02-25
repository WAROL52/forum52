<?php

namespace App\Controller;

use App\DTO\Auth\Request\CompletePasswordResetRequest;
use App\DTO\Auth\Request\CompleteRegistrationRequest;
use App\DTO\Auth\Request\LoginRequest;
use App\DTO\Auth\Request\RequestPasswordResetRequest;
use App\DTO\Auth\Request\RequestVerificationRequest;
use App\DTO\Auth\Response\AuthSuccessResponse;
use App\DTO\Auth\Response\PasswordResetSuccessResponse;
use App\DTO\Auth\Response\VerificationSentResponse;
use App\DTO\User\Response\UserResponse;
use App\Entity\User;
use App\Exception\ApiException;
use App\Service\UserService;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/', name: 'auth')]
class AuthController extends BaseApiController
{
    public function __construct(
        private UserService $userService,
        private JWTTokenManagerInterface $jwtManager,
        private int $jwtTtl,
    ) {
    }

    #[Route('/register/request-verification', name: 'register_request_verification', methods: ['POST'])]
    #[OA\Post(
        path: '/register/request-verification',
        summary: 'Request verification code for registration',
        tags: ['Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: new Model(type: RequestVerificationRequest::class))
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Verification code sent to email',
                content: new OA\JsonContent(ref: new Model(type: VerificationSentResponse::class))
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, ref: '#/components/responses/BadRequestError')
        ]
    )]
    public function requestVerification(Request $request): JsonResponse
    {
        $requestDto = $this->serializer->deserialize(
            $request->getContent(),
            RequestVerificationRequest::class,
            'json'
        );

        $this->validateRequest($requestDto);

        $this->userService->requestVerification($requestDto->email);

        $response = new VerificationSentResponse(
            'VERIFICATION_CODE_SENT',
            $requestDto->email
        );

        return $this->jsonResponse($response);
    }

    #[Route('/register/complete', name: 'register_complete', methods: ['POST'])]
    #[OA\Post(
        path: '/register/complete',
        summary: 'Complete registration with verification code',
        tags: ['Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: new Model(type: CompleteRegistrationRequest::class))
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_CREATED,
                description: 'Registration completed successfully',
                content: new OA\JsonContent(ref: new Model(type: AuthSuccessResponse::class))
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, ref: '#/components/responses/BadRequestError')
        ]
    )]
    public function completeRegistration(Request $request): JsonResponse
    {
        $registerRequest = $this->serializer->deserialize(
            $request->getContent(),
            CompleteRegistrationRequest::class,
            'json'
        );

        $this->validateRequest($registerRequest);

        $user = $this->userService->completeRegistration($registerRequest);
        $token = $this->jwtManager->create($user);
        $expiresAt = time() + $this->jwtTtl;

        $response = AuthSuccessResponse::create(
            'REGISTER_SUCCESS',
            $user,
            $token,
            $expiresAt
        );

        return $this->jsonResponse($response, Response::HTTP_CREATED);
    }

    #[Route('/password-reset/request', name: 'password_reset_request', methods: ['POST'])]
    #[OA\Post(
        path: '/password-reset/request',
        summary: 'Request password reset code',
        tags: ['Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: new Model(type: RequestPasswordResetRequest::class))
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Password reset code sent if email exists',
                content: new OA\JsonContent(ref: new Model(type: VerificationSentResponse::class))
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, ref: '#/components/responses/BadRequestError')
        ]
    )]
    public function requestPasswordReset(Request $request): JsonResponse
    {
        $requestDto = $this->serializer->deserialize(
            $request->getContent(),
            RequestPasswordResetRequest::class,
            'json'
        );

        $this->validateRequest($requestDto);

        $this->userService->requestPasswordReset($requestDto->email);

        $response = new VerificationSentResponse(
            'PASSWORD_RESET_CODE_SENT',
            $requestDto->email
        );

        return $this->jsonResponse($response);
    }

    #[Route('/password-reset/complete', name: 'password_reset_complete', methods: ['POST'])]
    #[OA\Post(
        path: '/password-reset/complete',
        summary: 'Complete password reset with code',
        tags: ['Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: new Model(type: CompletePasswordResetRequest::class))
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Password reset successful',
                content: new OA\JsonContent(ref: new Model(type: PasswordResetSuccessResponse::class))
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, ref: '#/components/responses/BadRequestError')
        ]
    )]
    public function completePasswordReset(Request $request): JsonResponse
    {
        $requestDto = $this->serializer->deserialize(
            $request->getContent(),
            CompletePasswordResetRequest::class,
            'json'
        );

        $this->validateRequest($requestDto);

        $this->userService->completePasswordReset($requestDto);

        $response = new PasswordResetSuccessResponse();

        return $this->jsonResponse($response);
    }

    #[Route('/login', name: 'login', methods: ['POST'])]
    #[OA\Post(
        path: '/login',
        summary: 'Login with email and password',
        tags: ['Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: new Model(type: LoginRequest::class))
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Login successful',
                content: new OA\JsonContent(ref: new Model(type: AuthSuccessResponse::class))
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, ref: '#/components/responses/BadRequestError'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, ref: '#/components/responses/UnauthorizedError')
        ]
    )]
    public function login(Request $request): JsonResponse
    {
        $loginRequest = $this->serializer->deserialize(
            $request->getContent(),
            LoginRequest::class,
            'json'
        );

        $this->validateRequest($loginRequest);

        $user = $this->userService->checkCredentials($loginRequest->email, $loginRequest->password);
        if (!$user) {
            throw new ApiException('Invalid credentials', Response::HTTP_UNAUTHORIZED);
        }

        $token = $this->jwtManager->create($user);
        $expiresAt = time() + $this->jwtTtl;

        $response = AuthSuccessResponse::create(
            'LOGIN_SUCCESS',
            $user,
            $token,
            $expiresAt
        );

        return $this->jsonResponse($response);
    }

    #[Route('/me', name: 'me', methods: ['GET'])]
    #[OA\Get(
        path: '/me',
        summary: 'Get current authenticated user information',
        tags: ['Auth'],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Current user information',
                content: new OA\JsonContent(ref: new Model(type: UserResponse::class))
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, ref: '#/components/responses/UnauthorizedError')
        ]
    )]
    #[IsGranted('ROLE_USER')]
    public function me(#[CurrentUser] User $currentUser): JsonResponse
    {
        $response = UserResponse::fromEntity($currentUser);

        return $this->jsonResponse($response);
    }

}
