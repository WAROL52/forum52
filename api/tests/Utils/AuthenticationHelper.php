<?php

namespace App\Tests\Utils;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AuthenticationHelper
{
    private KernelBrowser $client;

    public function __construct(KernelBrowser $client)
    {
        $this->client = $client;
    }

    /**
     * Create a test user and return JWT token
     */
    public function createAuthenticatedUser(
        string $email = 'test@example.com',
        string $password = 'password123',
        array $roles = ['ROLE_USER']
    ): array {
        $container = $this->client->getContainer();
        $em = $container->get(EntityManagerInterface::class);
        $passwordHasher = $container->get(UserPasswordHasherInterface::class);

        // Check if user already exists
        $existingUser = $em->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($existingUser) {
            $em->remove($existingUser);
            $em->flush();
        }

        // Create user
        $user = new User();
        $user->setEmail($email);
        $user->setFirstName('Test');
        $user->setLastName('User');
        $user->setRoles($roles);
        $hashedPassword = $passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);

        $em->persist($user);
        $em->flush();

        // Get JWT token
        $token = $this->login($email, $password);

        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    /**
     * Login and get JWT token
     */
    public function login(string $email, string $password): string
    {
        $this->client->request(
            'POST',
            '/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email' => $email,
                'password' => $password,
            ])
        );

        $response = $this->client->getResponse();
        $data = json_decode($response->getContent(), true);

        if (!isset($data['data']['token'])) {
            throw new \RuntimeException('Failed to login: ' . $response->getContent());
        }

        return $data['data']['token'];
    }

    /**
     * Create multiple test users
     */
    public function createMultipleUsers(int $count = 3): array
    {
        $users = [];
        for ($i = 1; $i <= $count; $i++) {
            $users[] = $this->createAuthenticatedUser(
                "user{$i}@example.com",
                'password123'
            );
        }
        return $users;
    }

    /**
     * Create an admin user
     */
    public function createAdminUser(): array
    {
        return $this->createAuthenticatedUser(
            'admin@example.com',
            'admin123',
            ['ROLE_USER', 'ROLE_ADMIN']
        );
    }
}
