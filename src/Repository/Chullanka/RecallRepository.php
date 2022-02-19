<?php

namespace App\Repository\Chullanka;

use App\Entity\Chullanka\Recall;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;

/**
 * @method Recall|null find($id, $lockMode = null, $lockVersion = null)
 * @method Recall|null findOneBy(array $criteria, array $orderBy = null)
 * @method Recall[]    findAll()
 * @method Recall[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RecallRepository extends EntityRepository
{
    /*public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Recall::class);
    }*/

    // /**
    //  * @return Recall[] Returns an array of Recall objects
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
    public function findOneBySomeField($value): ?Recall
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
