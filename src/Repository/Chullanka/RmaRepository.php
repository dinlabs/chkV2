<?php

namespace App\Repository\Chullanka;

use App\Entity\Chullanka\Rma;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Rma|null find($id, $lockMode = null, $lockVersion = null)
 * @method Rma|null findOneBy(array $criteria, array $orderBy = null)
 * @method Rma[]    findAll()
 * @method Rma[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RmaRepository extends ServiceEntityRepository
{
    /*public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Rma::class);
    }*/

    // /**
    //  * @return Rma[] Returns an array of Rma objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('r.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Rma
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
