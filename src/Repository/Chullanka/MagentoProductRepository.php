<?php

namespace App\Repository\Chullanka;

use App\Entity\Chullanka\MagentoProduct;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method MagentoProduct|null find($id, $lockMode = null, $lockVersion = null)
 * @method MagentoProduct|null findOneBy(array $criteria, array $orderBy = null)
 * @method MagentoProduct[]    findAll()
 * @method MagentoProduct[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MagentoProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MagentoProduct::class);
    }

    // /**
    //  * @return MagentoProduct[] Returns an array of MagentoProduct objects
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
    public function findOneBySomeField($value): ?MagentoProduct
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
