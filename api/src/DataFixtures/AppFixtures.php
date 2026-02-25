<?php

namespace App\DataFixtures;

use App\Entity\Post;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        // Create 5 test users
        $users = [];
        for ($i = 1; $i <= 5; $i++) {
            $user = new User();
            $user->setEmail("user{$i}@base.local");
            $user->setFirstName("User");
            $user->setLastName("Number {$i}");

            // Hash password (using 'password' for all users)
            $hashedPassword = $this->passwordHasher->hashPassword($user, 'demo');
            $user->setPassword($hashedPassword);

            $manager->persist($user);
            $users[] = $user;
        }

        // Flush users first to get their IDs
        $manager->flush();

        // Create 100 posts distributed across the 5 users
        for ($i = 1; $i <= 100; $i++) {
            $post = new Post();
            $post->setTitle("Post Title {$i}");
            $post->setContent("This is the content for post number {$i}. It contains some sample text to demonstrate the post content.");

            // Distribute posts evenly across users
            $userIndex = ($i - 1) % 5;
            $post->setAuthor($users[$userIndex]);

            $manager->persist($post);
        }

        $manager->flush();
    }
}
