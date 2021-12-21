<?php

namespace App\Repository\Chullanka;

use App\Entity\Chullanka\Chulltest;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;

/**
 * @method Chulltest|null find($id, $lockMode = null, $lockVersion = null)
 * @method Chulltest|null findOneBy(array $criteria, array $orderBy = null)
 * @method Chulltest[]    findAll()
 * @method Chulltest[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ChulltestRepository extends EntityRepository
{
    /*public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Chulltest::class);
    }*/

    // /**
    //  * @return Chulltest[] Returns an array of Chulltest objects
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
    public function findOneBySomeField($value): ?Chulltest
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
