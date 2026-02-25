<?php

namespace App\Repository;

use App\DTO\Shared\FilterCollection;
use App\Entity\Post;
use App\Helper\QueryFilter;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Post>
 */
class PostRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
        private QueryFilter $queryFilter
    ) {
        parent::__construct($registry, Post::class);
    }

    /**
     * Crée la query pour récupérer tous les posts avec support des filtres génériques
     *
     * @param FilterCollection $filters Filtres à appliquer
     * @return Query
     */
    public function createFindAllQuery(FilterCollection $filters): Query
    {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.author', 'a')
            ->addSelect('a')
            ->orderBy('p.createdAt', 'DESC');

        // Apply generic filters
        $this->queryFilter->applyFilters($qb, $filters, Post::class, 'p');

        return $qb->getQuery();
    }

    /**
     * Compte le nombre total de posts avec support des filtres génériques
     *
     * @param FilterCollection $filters Filtres à appliquer
     * @return int
     */
    public function countAll(FilterCollection $filters): int
    {
        $qb = $this->createQueryBuilder('p')
            ->select('COUNT(p.id)');

        // Apply generic filters
        $this->queryFilter->applyFilters($qb, $filters, Post::class, 'p');

        return $qb->getQuery()->getSingleScalarResult();
    }
}
