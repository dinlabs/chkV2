<?php

namespace App\Repository\Chullanka;

use App\Entity\Chullanka\MagentoOrder;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method MagentoOrder|null find($id, $lockMode = null, $lockVersion = null)
 * @method MagentoOrder|null findOneBy(array $criteria, array $orderBy = null)
 * @method MagentoOrder[]    findAll()
 * @method MagentoOrder[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MagentoOrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MagentoOrder::class);
    }

    // /**
    //  * @return MagentoOrder[] Returns an array of MagentoOrder objects
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
    public function findOneBySomeField($value): ?MagentoOrder
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
