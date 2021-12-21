<?php

namespace App\Repository\Chullanka;

use App\Entity\Chullanka\Chulli;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;

/**
 * @method Chulli|null find($id, $lockMode = null, $lockVersion = null)
 * @method Chulli|null findOneBy(array $criteria, array $orderBy = null)
 * @method Chulli[]    findAll()
 * @method Chulli[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ChulliRepository extends EntityRepository
{
    /*public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Chulli::class);
    }*/

    // /**
    //  * @return Chulli[] Returns an array of Chulli objects
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
    public function findOneBySomeField($value): ?Chulli
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
