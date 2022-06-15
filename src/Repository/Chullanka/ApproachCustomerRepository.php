<?php

namespace App\Repository\Chullanka;

use App\Entity\Chullanka\ApproachCustomer;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ApproachCustomer|null find($id, $lockMode = null, $lockVersion = null)
 * @method ApproachCustomer|null findOneBy(array $criteria, array $orderBy = null)
 * @method ApproachCustomer[]    findAll()
 * @method ApproachCustomer[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ApproachCustomerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ApproachCustomer::class);
    }

    // /**
    //  * @return ApproachCustomer[] Returns an array of ApproachCustomer objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('m.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?ApproachCustomer
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
