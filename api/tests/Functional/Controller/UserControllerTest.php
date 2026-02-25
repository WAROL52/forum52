<?php

namespace App\Tests\Functional\Controller;

use App\Tests\Utils\ApiTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;

class UserControllerTest extends ApiTestCase
{
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        parent::setUp();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
    }

    public function testGetUserRequiresAuthentication(): void
    {
        $this->request('GET', '/users/1');

        $this->assertResponseStatusCode(Response::HTTP_UNAUTHORIZED);
    }

    public function testGetCurrentUser(): void
    {
        $auth = $this->authHelper->createAuthenticatedUser('test@example.com');
        $userId = $auth['user']->getId();

        $this->request('GET', "/users/{$userId}", [], $auth['token']);

        $this->assertResponseStatusCode(Response::HTTP_OK);
        $this->assertJsonResponseHasKeys(['id', 'email', 'firstName', 'lastName', 'createdAt']);
        $this->assertJsonResponseContains('email', 'test@example.com');
    }

    public function testCannotGetAnotherUserProfile(): void
    {
        $user1 = $this->authHelper->createAuthenticatedUser('user1@example.com');
        $user2 = $this->authHelper->createAuthenticatedUser('user2@example.com');

        // User1 tries to access User2's profile (should be forbidden)
        $this->request('GET', "/users/{$user2['user']->getId()}", [], $user1['token']);

        $this->assertResponseStatusCode(Response::HTTP_FORBIDDEN);
    }

    public function testGetNonExistentUser(): void
    {
        $auth = $this->authHelper->createAuthenticatedUser();

        $this->request('GET', '/users/99999', [], $auth['token']);

        $this->assertResponseStatusCode(Response::HTTP_NOT_FOUND);
    }

    public function testListUsers(): void
    {
        // Create multiple users
        $users = $this->authHelper->createMultipleUsers(5);

        $this->request('GET', '/users', [], $users[0]['token']);

        $this->assertResponseStatusCode(Response::HTTP_OK);
        $this->assertJsonResponseHasKeys(['data', 'pagination']);

        $data = $this->getJsonResponse();
        $this->assertIsArray($data['data']);
        $this->assertGreaterThanOrEqual(5, count($data['data']));
    }

    public function testListUsersWithPagination(): void
    {
        $users = $this->authHelper->createMultipleUsers(5);

        $this->request('GET', '/users?page=1&limit=2', [], $users[0]['token']);

        $this->assertResponseStatusCode(Response::HTTP_OK);

        $data = $this->getJsonResponse();
        $this->assertCount(2, $data['data']);
        $this->assertEquals(1, $data['pagination']['page']);
        $this->assertEquals(2, $data['pagination']['limit']);
    }

    public function testUpdateOwnProfile(): void
    {
        $auth = $this->authHelper->createAuthenticatedUser('user@example.com');
        $userId = $auth['user']->getId();

        $this->request('PUT', "/users/{$userId}", [
            'firstName' => 'UpdatedFirst',
            'lastName' => 'UpdatedLast',
        ], $auth['token']);

        $this->assertResponseStatusCode(Response::HTTP_OK);
        $this->assertJsonResponseContains('firstName', 'UpdatedFirst');
        $this->assertJsonResponseContains('lastName', 'UpdatedLast');
        $this->assertJsonResponseContains('email', 'user@example.com');
    }

    public function testCannotUpdateAnotherUserProfile(): void
    {
        $user1 = $this->authHelper->createAuthenticatedUser('user1@example.com');
        $user2 = $this->authHelper->createAuthenticatedUser('user2@example.com');

        // User1 tries to update User2's profile
        $this->request('PUT', "/users/{$user2['user']->getId()}", [
            'firstName' => 'Hacked',
            'lastName' => 'User',
        ], $user1['token']);

        $this->assertResponseStatusCode(Response::HTTP_FORBIDDEN);
    }

    public function testAdminCanUpdateAnyUser(): void
    {
        $admin = $this->authHelper->createAdminUser();
        $user = $this->authHelper->createAuthenticatedUser('user@example.com');

        $this->request('PUT', "/users/{$user['user']->getId()}", [
            'firstName' => 'AdminUpdated',
            'lastName' => 'User',
        ], $admin['token']);

        $this->assertResponseStatusCode(Response::HTTP_OK);
        $this->assertJsonResponseContains('firstName', 'AdminUpdated');
    }

    public function testDeleteOwnAccount(): void
    {
        $auth = $this->authHelper->createAuthenticatedUser('todelete@example.com');
        $userId = $auth['user']->getId();

        $this->request('DELETE', "/users/{$userId}", [], $auth['token']);

        $this->assertResponseStatusCode(Response::HTTP_NO_CONTENT);

        // Verify user was deleted
        $this->em->clear();
        $deletedUser = $this->em->getRepository(\App\Entity\User::class)->find($userId);
        $this->assertNull($deletedUser);
    }

    public function testCannotDeleteAnotherUserAccount(): void
    {
        $user1 = $this->authHelper->createAuthenticatedUser('user1@example.com');
        $user2 = $this->authHelper->createAuthenticatedUser('user2@example.com');

        $this->request('DELETE', "/users/{$user2['user']->getId()}", [], $user1['token']);

        $this->assertResponseStatusCode(Response::HTTP_FORBIDDEN);
    }

    public function testAdminCanDeleteAnyUser(): void
    {
        $admin = $this->authHelper->createAdminUser();
        $user = $this->authHelper->createAuthenticatedUser('user@example.com');
        $userId = $user['user']->getId();

        $this->request('DELETE', "/users/{$userId}", [], $admin['token']);

        $this->assertResponseStatusCode(Response::HTTP_NO_CONTENT);

        // Verify user was deleted
        $this->em->clear();
        $deletedUser = $this->em->getRepository(\App\Entity\User::class)->find($userId);
        $this->assertNull($deletedUser);
    }

    public function testUpdateWithShortFirstName(): void
    {
        $auth = $this->authHelper->createAuthenticatedUser('user@example.com');
        $userId = $auth['user']->getId();

        $this->request('PUT', "/users/{$userId}", [
            'firstName' => 'A',
            'lastName' => 'User',
        ], $auth['token']);

        $this->assertValidationError();
    }

    public function testUpdateWithMissingFields(): void
    {
        $auth = $this->authHelper->createAuthenticatedUser('user@example.com');
        $userId = $auth['user']->getId();

        $this->request('PUT', "/users/{$userId}", [
            'firstName' => '',
            'lastName' => '',
        ], $auth['token']);

        $this->assertValidationError();
    }
}
