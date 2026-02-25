<?php

namespace App\Tests\Utils;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

abstract class ApiTestCase extends WebTestCase
{
    protected KernelBrowser $client;
    protected AuthenticationHelper $authHelper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
        $this->authHelper = new AuthenticationHelper($this->client);
    }

    /**
     * Make a JSON API request
     */
    protected function request(
        string $method,
        string $uri,
        array $data = [],
        ?string $token = null
    ): Response {
        $headers = [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
        ];

        if ($token) {
            $headers['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;
        }

        $this->client->request(
            $method,
            $uri,
            [],
            [],
            $headers,
            empty($data) ? null : json_encode($data)
        );

        return $this->client->getResponse();
    }

    /**
     * Get JSON response as array
     */
    protected function getJsonResponse(): array
    {
        $response = $this->client->getResponse();
        $content = $response->getContent();

        if (empty($content)) {
            return [];
        }

        return json_decode($content, true) ?? [];
    }

    /**
     * Assert response status code
     */
    protected function assertResponseStatusCode(int $expected): void
    {
        $actual = $this->client->getResponse()->getStatusCode();
        $this->assertEquals(
            $expected,
            $actual,
            sprintf(
                'Expected status code %d, got %d. Response: %s',
                $expected,
                $actual,
                $this->client->getResponse()->getContent()
            )
        );
    }

    /**
     * Assert JSON response has key
     */
    protected function assertJsonResponseHasKey(string $key): void
    {
        $data = $this->getJsonResponse();
        $this->assertArrayHasKey($key, $data);
    }

    /**
     * Assert JSON response has keys
     */
    protected function assertJsonResponseHasKeys(array $keys): void
    {
        $data = $this->getJsonResponse();
        foreach ($keys as $key) {
            $this->assertArrayHasKey($key, $data);
        }
    }

    /**
     * Assert JSON response contains value
     */
    protected function assertJsonResponseContains(string $key, mixed $value): void
    {
        $data = $this->getJsonResponse();
        $this->assertArrayHasKey($key, $data);
        $this->assertEquals($value, $data[$key]);
    }

    /**
     * Assert validation error response
     */
    protected function assertValidationError(): void
    {
        $this->assertResponseStatusCode(Response::HTTP_BAD_REQUEST);
        $this->assertJsonResponseHasKey('code');
    }
}
