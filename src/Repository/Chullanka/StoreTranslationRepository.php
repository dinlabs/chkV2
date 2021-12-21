<?php

namespace App\Repository\Chullanka;

use App\Entity\Chullanka\StoreTranslation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;

/**
 * @method StoreTranslation|null find($id, $lockMode = null, $lockVersion = null)
 * @method StoreTranslation|null findOneBy(array $criteria, array $orderBy = null)
 * @method StoreTranslation[]    findAll()
 * @method StoreTranslation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class StoreTranslationRepository extends EntityRepository
{
    /*public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StoreTranslation::class);
    }*/

    // /**
    //  * @return StoreTranslation[] Returns an array of StoreTranslation objects
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
    public function findOneBySomeField($value): ?StoreTranslation
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
