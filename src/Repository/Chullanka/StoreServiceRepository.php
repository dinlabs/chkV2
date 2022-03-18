<?php

namespace App\Repository\Chullanka;

use App\Entity\Chullanka\StoreService;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;

/**
 * @method StoreService|null find($id, $lockMode = null, $lockVersion = null)
 * @method StoreService|null findOneBy(array $criteria, array $orderBy = null)
 * @method StoreService[]    findAll()
 * @method StoreService[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class StoreServiceRepository extends EntityRepository
{
    /*public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StoreService::class);
    }*/

    // /**
    //  * @return StoreService[] Returns an array of StoreService objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('s.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?StoreService
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
