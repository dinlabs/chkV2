<?php

namespace App\Repository\Chullanka;

use App\Entity\Chullanka\WishlistProduct;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method WishlistProduct|null find($id, $lockMode = null, $lockVersion = null)
 * @method WishlistProduct|null findOneBy(array $criteria, array $orderBy = null)
 * @method WishlistProduct[]    findAll()
 * @method WishlistProduct[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class WishlistProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WishlistProduct::class);
    }

    // /**
    //  * @return WishlistProduct[] Returns an array of WishlistProduct objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('w')
            ->andWhere('w.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('w.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?WishlistProduct
    {
        return $this->createQueryBuilder('w')
            ->andWhere('w.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
