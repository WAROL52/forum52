<?php

namespace App\Service;

use App\DTO\Post\Request\PostRequest;
use App\DTO\Shared\FilterCollection;
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
     * Get all posts with pagination and filters
     */
    public function list(int $page = 1, int $limit = 10, FilterCollection $filters = null): array
    {
        $filters = $filters ?? new FilterCollection();
        $query = $this->postRepository->createFindAllQuery($filters);
        $total = $this->postRepository->countAll($filters);

        return $this->paginate->get($query, $page, $limit, $total);
    }
}
