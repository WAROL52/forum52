<?php

namespace App\Repository;

use App\Entity\PendingVerification;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PendingVerification>
 */
class PendingVerificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PendingVerification::class);
    }

    public function deleteExpired(): int
    {
        return $this->createQueryBuilder('pv')
            ->delete()
            ->where('pv.expiresAt < :now')
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->execute();
    }
}
