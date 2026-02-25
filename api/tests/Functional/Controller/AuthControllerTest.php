<?php

namespace App\Tests\Functional\Controller;

use App\Entity\PendingVerification;
use App\Entity\User;
use App\Tests\Utils\ApiTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;

class AuthControllerTest extends ApiTestCase
{
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        parent::setUp();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
    }

    public function testLoginWithValidCredentials(): void
    {
        // Create a user
        $this->authHelper->createAuthenticatedUser('test@example.com', 'password123');

        // Login
        $this->request('POST', '/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $this->assertResponseStatusCode(Response::HTTP_OK);
        $this->assertJsonResponseHasKeys(['code', 'data']);
        $this->assertJsonResponseContains('code', 'LOGIN_SUCCESS');

        $data = $this->getJsonResponse();
        $this->assertNotEmpty($data['data']['token']);
        $this->assertEquals('test@example.com', $data['data']['user']['email']);
    }

    public function testLoginWithInvalidCredentials(): void
    {
        $this->authHelper->createAuthenticatedUser('test@example.com', 'password123');

        $this->request('POST', '/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ]);

        $this->assertResponseStatusCode(Response::HTTP_UNAUTHORIZED);
    }

    public function testLoginWithNonExistentUser(): void
    {
        $this->request('POST', '/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'password123',
        ]);

        $this->assertResponseStatusCode(Response::HTTP_UNAUTHORIZED);
    }

    public function testLoginValidationError(): void
    {
        $this->request('POST', '/login', [
            'email' => 'invalid-email',
            'password' => '',
        ]);

        $this->assertValidationError();
    }

    public function testRequestVerificationWithValidEmail(): void
    {
        $this->request('POST', '/register/request-verification', [
            'email' => 'newuser@example.com',
        ]);

        $this->assertResponseStatusCode(Response::HTTP_OK);
        $this->assertJsonResponseHasKeys(['code', 'data']);
        $this->assertJsonResponseContains('code', 'VERIFICATION_CODE_SENT');

        $data = $this->getJsonResponse();
        $this->assertEquals('newuser@example.com', $data['data']['email']);

        // Verify that a pending verification was created
        $pending = $this->em->getRepository(PendingVerification::class)
            ->findOneBy(['email' => 'newuser@example.com']);
        $this->assertNotNull($pending);
        $this->assertEquals(PendingVerification::TYPE_REGISTRATION, $pending->getType());
    }

    public function testRequestVerificationWithExistingEmail(): void
    {
        // Create existing user
        $this->authHelper->createAuthenticatedUser('existing@example.com');

        $this->request('POST', '/register/request-verification', [
            'email' => 'existing@example.com',
        ]);

        $this->assertResponseStatusCode(Response::HTTP_BAD_REQUEST);
    }

    public function testCompleteRegistrationWithValidCode(): void
    {
        // Request verification
        $this->request('POST', '/register/request-verification', [
            'email' => 'newuser@example.com',
        ]);

        // Get the verification code from database
        $pending = $this->em->getRepository(PendingVerification::class)
            ->findOneBy(['email' => 'newuser@example.com']);
        $code = $pending->getCode();

        // Complete registration
        $this->request('POST', '/register/complete', [
            'email' => 'newuser@example.com',
            'code' => $code,
            'password' => 'SecurePass123!',
            'firstName' => 'John',
            'lastName' => 'Doe',
        ]);

        $this->assertResponseStatusCode(Response::HTTP_CREATED);
        $this->assertJsonResponseHasKeys(['code', 'data']);
        $this->assertJsonResponseContains('code', 'REGISTER_SUCCESS');

        // Verify user was created
        $user = $this->em->getRepository(User::class)
            ->findOneBy(['email' => 'newuser@example.com']);
        $this->assertNotNull($user);
        $this->assertEquals('John', $user->getFirstName());
        $this->assertEquals('Doe', $user->getLastName());
    }

    public function testCompleteRegistrationWithInvalidCode(): void
    {
        $this->request('POST', '/register/request-verification', [
            'email' => 'newuser@example.com',
        ]);

        $this->request('POST', '/register/complete', [
            'email' => 'newuser@example.com',
            'code' => '000000',
            'password' => 'SecurePass123!',
            'firstName' => 'John',
            'lastName' => 'Doe',
        ]);

        $this->assertResponseStatusCode(Response::HTTP_BAD_REQUEST);
    }

    public function testRequestPasswordReset(): void
    {
        $this->authHelper->createAuthenticatedUser('user@example.com');

        $this->request('POST', '/password-reset/request', [
            'email' => 'user@example.com',
        ]);

        $this->assertResponseStatusCode(Response::HTTP_OK);
        $this->assertJsonResponseContains('code', 'PASSWORD_RESET_CODE_SENT');

        // Verify pending verification was created
        $pending = $this->em->getRepository(PendingVerification::class)
            ->findOneBy(['email' => 'user@example.com', 'type' => PendingVerification::TYPE_PASSWORD_RESET]);
        $this->assertNotNull($pending);
    }

    public function testRequestPasswordResetWithNonExistentEmail(): void
    {
        // Should not reveal if email exists
        $this->request('POST', '/password-reset/request', [
            'email' => 'nonexistent@example.com',
        ]);

        $this->assertResponseStatusCode(Response::HTTP_OK);
        $this->assertJsonResponseContains('code', 'PASSWORD_RESET_CODE_SENT');
    }

    public function testCompletePasswordResetWithValidCode(): void
    {
        $this->authHelper->createAuthenticatedUser('user@example.com', 'oldPassword');

        // Request password reset
        $this->request('POST', '/password-reset/request', [
            'email' => 'user@example.com',
        ]);

        // Get the code
        $pending = $this->em->getRepository(PendingVerification::class)
            ->findOneBy(['email' => 'user@example.com', 'type' => PendingVerification::TYPE_PASSWORD_RESET]);
        $code = $pending->getCode();

        // Complete password reset
        $this->request('POST', '/password-reset/complete', [
            'email' => 'user@example.com',
            'code' => $code,
            'newPassword' => 'NewSecurePass123!',
        ]);

        $this->assertResponseStatusCode(Response::HTTP_OK);
        $this->assertJsonResponseContains('code', 'PASSWORD_RESET_SUCCESS');

        // Verify can login with new password
        $this->request('POST', '/login', [
            'email' => 'user@example.com',
            'password' => 'NewSecurePass123!',
        ]);

        $this->assertResponseStatusCode(Response::HTTP_OK);
    }

    public function testCompletePasswordResetWithInvalidCode(): void
    {
        $this->authHelper->createAuthenticatedUser('user@example.com');

        $this->request('POST', '/password-reset/request', [
            'email' => 'user@example.com',
        ]);

        $this->request('POST', '/password-reset/complete', [
            'email' => 'user@example.com',
            'code' => '000000',
            'newPassword' => 'NewPassword123!',
        ]);

        $this->assertResponseStatusCode(Response::HTTP_BAD_REQUEST);
    }
}
