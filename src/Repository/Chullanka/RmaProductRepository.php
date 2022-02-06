<?php

namespace App\Repository\Chullanka;

use App\Entity\Chullanka\RmaProduct;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method RmaProduct|null find($id, $lockMode = null, $lockVersion = null)
 * @method RmaProduct|null findOneBy(array $criteria, array $orderBy = null)
 * @method RmaProduct[]    findAll()
 * @method RmaProduct[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RmaProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RmaProduct::class);
    }

    // /**
    //  * @return RmaProduct[] Returns an array of RmaProduct objects
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
    public function findOneBySomeField($value): ?RmaProduct
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
