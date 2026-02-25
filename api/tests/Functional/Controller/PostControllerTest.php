<?php

namespace App\Tests\Functional\Controller;

use App\Entity\Post;
use App\Tests\Utils\ApiTestCase;
use App\Tests\Utils\FixturesLoader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;

class PostControllerTest extends ApiTestCase
{
    private EntityManagerInterface $em;
    private FixturesLoader $fixturesLoader;

    protected function setUp(): void
    {
        parent::setUp();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
        $this->fixturesLoader = new FixturesLoader(
            $this->em,
            static::getContainer()->get('security.user_password_hasher')
        );

        // Clear database
        $this->fixturesLoader->clearDatabase();
    }

    public function testListPostsRequiresAuthentication(): void
    {
        $this->request('GET', '/posts');

        $this->assertResponseStatusCode(Response::HTTP_UNAUTHORIZED);
    }

    public function testListPostsReturnsAllPosts(): void
    {
        $auth = $this->authHelper->createAuthenticatedUser('user@example.com');
        $this->fixturesLoader->createMultiplePosts($auth['user'], 3);

        $this->request('GET', '/posts', [], $auth['token']);

        $this->assertResponseStatusCode(Response::HTTP_OK);
        $this->assertJsonResponseHasKeys(['data', 'pagination']);

        $data = $this->getJsonResponse();
        $this->assertCount(3, $data['data']);
    }

    public function testListPostsWithPagination(): void
    {
        $auth = $this->authHelper->createAuthenticatedUser('user@example.com');
        $this->fixturesLoader->createMultiplePosts($auth['user'], 10);

        $this->request('GET', '/posts?page=1&limit=5', [], $auth['token']);

        $this->assertResponseStatusCode(Response::HTTP_OK);

        $data = $this->getJsonResponse();
        $this->assertCount(5, $data['data']);
        $this->assertEquals(1, $data['pagination']['page']);
        $this->assertEquals(5, $data['pagination']['limit']);
        $this->assertEquals(10, $data['pagination']['total']);
    }

    public function testListPostsFilteredByAuthor(): void
    {
        $user1 = $this->authHelper->createAuthenticatedUser('user1@example.com');
        $user2 = $this->authHelper->createAuthenticatedUser('user2@example.com');

        $this->fixturesLoader->createMultiplePosts($user1['user'], 3);
        $this->fixturesLoader->createMultiplePosts($user2['user'], 2);

        $this->request(
            'GET',
            '/posts?authorId=' . $user1['user']->getId(),
            [],
            $user1['token']
        );

        $this->assertResponseStatusCode(Response::HTTP_OK);

        $data = $this->getJsonResponse();
        $this->assertCount(3, $data['data']);

        // Verify all posts belong to user1
        foreach ($data['data'] as $post) {
            $this->assertEquals($user1['user']->getId(), $post['author']['id']);
        }
    }

    public function testGetPostById(): void
    {
        $auth = $this->authHelper->createAuthenticatedUser('user@example.com');
        $post = $this->fixturesLoader->createPost($auth['user'], 'Test Title', 'Test Content');

        $this->request('GET', "/posts/{$post->getId()}", [], $auth['token']);

        $this->assertResponseStatusCode(Response::HTTP_OK);
        $this->assertJsonResponseContains('title', 'Test Title');
        $this->assertJsonResponseContains('content', 'Test Content');
        $this->assertJsonResponseHasKey('author');
    }

    public function testGetNonExistentPost(): void
    {
        $auth = $this->authHelper->createAuthenticatedUser('user@example.com');

        $this->request('GET', '/posts/99999', [], $auth['token']);

        $this->assertResponseStatusCode(Response::HTTP_NOT_FOUND);
    }

    public function testCreatePost(): void
    {
        $auth = $this->authHelper->createAuthenticatedUser('user@example.com');

        $this->request('POST', '/posts', [
            'title' => 'New Post Title',
            'content' => 'This is the content of my new post.',
        ], $auth['token']);

        $this->assertResponseStatusCode(Response::HTTP_CREATED);
        $this->assertJsonResponseContains('title', 'New Post Title');
        $this->assertJsonResponseContains('content', 'This is the content of my new post.');

        $data = $this->getJsonResponse();
        $this->assertEquals($auth['user']->getId(), $data['author']['id']);
    }

    public function testCreatePostWithMissingFields(): void
    {
        $auth = $this->authHelper->createAuthenticatedUser('user@example.com');

        $this->request('POST', '/posts', [
            'title' => '',
        ], $auth['token']);

        $this->assertValidationError();
    }

    public function testCreatePostRequiresAuthentication(): void
    {
        $this->request('POST', '/posts', [
            'title' => 'Test',
            'content' => 'Test content',
        ]);

        $this->assertResponseStatusCode(Response::HTTP_UNAUTHORIZED);
    }

    public function testUpdateOwnPost(): void
    {
        $auth = $this->authHelper->createAuthenticatedUser('user@example.com');
        $post = $this->fixturesLoader->createPost($auth['user'], 'Original Title', 'Original Content');

        $this->request('PUT', "/posts/{$post->getId()}", [
            'title' => 'Updated Title',
            'content' => 'Updated Content',
        ], $auth['token']);

        $this->assertResponseStatusCode(Response::HTTP_OK);
        $this->assertJsonResponseContains('title', 'Updated Title');
        $this->assertJsonResponseContains('content', 'Updated Content');
    }

    public function testCannotUpdateAnotherUserPost(): void
    {
        $user1 = $this->authHelper->createAuthenticatedUser('user1@example.com');
        $user2 = $this->authHelper->createAuthenticatedUser('user2@example.com');

        $post = $this->fixturesLoader->createPost($user2['user'], 'User2 Post', 'Content');

        $this->request('PUT', "/posts/{$post->getId()}", [
            'title' => 'Hacked Title',
            'content' => 'Hacked Content',
        ], $user1['token']);

        $this->assertResponseStatusCode(Response::HTTP_FORBIDDEN);
    }

    public function testAdminCanUpdateAnyPost(): void
    {
        $admin = $this->authHelper->createAdminUser();
        $user = $this->authHelper->createAuthenticatedUser('user@example.com');

        $post = $this->fixturesLoader->createPost($user['user'], 'User Post', 'Content');

        $this->request('PUT', "/posts/{$post->getId()}", [
            'title' => 'Admin Updated',
            'content' => 'Admin updated content',
        ], $admin['token']);

        $this->assertResponseStatusCode(Response::HTTP_OK);
        $this->assertJsonResponseContains('title', 'Admin Updated');
    }

    public function testDeleteOwnPost(): void
    {
        $auth = $this->authHelper->createAuthenticatedUser('user@example.com');
        $post = $this->fixturesLoader->createPost($auth['user'], 'To Delete', 'Content');
        $postId = $post->getId();

        $this->request('DELETE', "/posts/{$postId}", [], $auth['token']);

        $this->assertResponseStatusCode(Response::HTTP_NO_CONTENT);

        // Verify post was deleted
        $this->em->clear();
        $deletedPost = $this->em->getRepository(Post::class)->find($postId);
        $this->assertNull($deletedPost);
    }

    public function testCannotDeleteAnotherUserPost(): void
    {
        $user1 = $this->authHelper->createAuthenticatedUser('user1@example.com');
        $user2 = $this->authHelper->createAuthenticatedUser('user2@example.com');

        $post = $this->fixturesLoader->createPost($user2['user'], 'User2 Post', 'Content');

        $this->request('DELETE', "/posts/{$post->getId()}", [], $user1['token']);

        $this->assertResponseStatusCode(Response::HTTP_FORBIDDEN);
    }

    public function testAdminCanDeleteAnyPost(): void
    {
        $admin = $this->authHelper->createAdminUser();
        $user = $this->authHelper->createAuthenticatedUser('user@example.com');

        $post = $this->fixturesLoader->createPost($user['user'], 'User Post', 'Content');
        $postId = $post->getId();

        $this->request('DELETE', "/posts/{$postId}", [], $admin['token']);

        $this->assertResponseStatusCode(Response::HTTP_NO_CONTENT);

        // Verify post was deleted
        $this->em->clear();
        $deletedPost = $this->em->getRepository(Post::class)->find($postId);
        $this->assertNull($deletedPost);
    }

    public function testPostResponseContainsAuthorDetails(): void
    {
        $auth = $this->authHelper->createAuthenticatedUser('user@example.com');
        $post = $this->fixturesLoader->createPost($auth['user'], 'Test Post', 'Content');

        $this->request('GET', "/posts/{$post->getId()}", [], $auth['token']);

        $this->assertResponseStatusCode(Response::HTTP_OK);

        $data = $this->getJsonResponse();
        $this->assertArrayHasKey('author', $data);
        $this->assertArrayHasKey('id', $data['author']);
        $this->assertArrayHasKey('email', $data['author']);
        $this->assertArrayHasKey('firstName', $data['author']);
        $this->assertArrayHasKey('lastName', $data['author']);
    }
}
