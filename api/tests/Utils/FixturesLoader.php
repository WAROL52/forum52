<?php

namespace App\Tests\Utils;

use App\Entity\Post;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class FixturesLoader
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $passwordHasher
    ) {
    }

    /**
     * Clear all data from database
     */
    public function clearDatabase(): void
    {
        // Nothing needed - tests handle isolation themselves
    }

    /**
     * Create a test user
     */
    public function createUser(
        string $email,
        string $password = 'password123',
        string $firstName = 'John',
        string $lastName = 'Doe',
        array $roles = ['ROLE_USER']
    ): User {
        $user = new User();
        $user->setEmail($email);
        $user->setFirstName($firstName);
        $user->setLastName($lastName);
        $user->setRoles($roles);
        $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);

        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    /**
     * Create a test post
     */
    public function createPost(
        User $author,
        string $title = 'Test Post',
        string $content = 'Test content'
    ): Post {
        // Reload the author from database to ensure it's managed
        $managedAuthor = $this->em->find(User::class, $author->getId());
        if (!$managedAuthor) {
            throw new \RuntimeException('Author user not found in database');
        }

        $post = new Post();
        $post->setTitle($title);
        $post->setContent($content);
        $post->setAuthor($managedAuthor);

        $this->em->persist($post);
        $this->em->flush();

        return $post;
    }

    /**
     * Create multiple posts for a user
     */
    public function createMultiplePosts(User $author, int $count = 5): array
    {
        $posts = [];
        for ($i = 1; $i <= $count; $i++) {
            $posts[] = $this->createPost(
                $author,
                "Test Post {$i}",
                "Content for post {$i}"
            );
        }
        return $posts;
    }

    /**
     * Load a complete test dataset
     */
    public function loadCompleteDataset(): array
    {
        $this->clearDatabase();

        // Create users
        $user1 = $this->createUser('user1@example.com', 'password123', 'Alice', 'Smith');
        $user2 = $this->createUser('user2@example.com', 'password123', 'Bob', 'Jones');
        $admin = $this->createUser('admin@example.com', 'admin123', 'Admin', 'User', ['ROLE_USER', 'ROLE_ADMIN']);

        // Create posts
        $posts1 = $this->createMultiplePosts($user1, 3);
        $posts2 = $this->createMultiplePosts($user2, 2);

        return [
            'users' => [$user1, $user2, $admin],
            'posts' => array_merge($posts1, $posts2),
        ];
    }
}
