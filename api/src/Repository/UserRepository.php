<?php

namespace App\Repository;

use App\DTO\Shared\FilterCollection;
use App\Entity\User;
use App\Helper\QueryFilter;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(
        ManagerRegistry $registry,
        private QueryFilter $queryFilter
    ) {
        parent::__construct($registry, User::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    /**
     * Crée la query pour récupérer tous les utilisateurs avec support des filtres génériques
     *
     * @param FilterCollection $filters Filtres à appliquer
     * @return Query
     */
    public function createFindAllQuery(FilterCollection $filters): Query
    {
        $qb = $this->createQueryBuilder('u')
            ->orderBy('u.createdAt', 'DESC');

        // Apply generic filters
        $this->queryFilter->applyFilters($qb, $filters, User::class, 'u');

        return $qb->getQuery();
    }

    /**
     * Compte le nombre total d'utilisateurs avec support des filtres génériques
     *
     * @param FilterCollection $filters Filtres à appliquer
     * @return int
     */
    public function countAll(FilterCollection $filters): int
    {
        $qb = $this->createQueryBuilder('u')
            ->select('COUNT(u.id)');

        // Apply generic filters
        $this->queryFilter->applyFilters($qb, $filters, User::class, 'u');

        return $qb->getQuery()->getSingleScalarResult();
    }
}
