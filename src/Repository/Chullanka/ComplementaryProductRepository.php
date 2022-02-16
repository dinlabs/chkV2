<?php

namespace App\Repository\Chullanka;

use App\Entity\Chullanka\ComplementaryProduct;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;

/**
 * @method ComplementaryProduct|null find($id, $lockMode = null, $lockVersion = null)
 * @method ComplementaryProduct|null findOneBy(array $criteria, array $orderBy = null)
 * @method ComplementaryProduct[]    findAll()
 * @method ComplementaryProduct[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ComplementaryProductRepository extends EntityRepository
{
    /*public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ComplementaryProduct::class);
    }*/

    // /**
    //  * @return ComplementaryProduct[] Returns an array of ComplementaryProduct objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('c.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?ComplementaryProduct
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
