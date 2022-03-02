<?php

namespace App\Repository\Chullanka;

use App\Entity\Chullanka\MagentoCustomer;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method MagentoCustomer|null find($id, $lockMode = null, $lockVersion = null)
 * @method MagentoCustomer|null findOneBy(array $criteria, array $orderBy = null)
 * @method MagentoCustomer[]    findAll()
 * @method MagentoCustomer[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MagentoCustomerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MagentoCustomer::class);
    }

    // /**
    //  * @return MagentoCustomer[] Returns an array of MagentoCustomer objects
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
    public function findOneBySomeField($value): ?MagentoCustomer
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
