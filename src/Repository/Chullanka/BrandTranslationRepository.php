<?php

namespace App\Repository\Chullanka;

use App\Entity\Chullanka\BrandTranslation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;

/**
 * @method BrandTranslation|null find($id, $lockMode = null, $lockVersion = null)
 * @method BrandTranslation|null findOneBy(array $criteria, array $orderBy = null)
 * @method BrandTranslation[]    findAll()
 * @method BrandTranslation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BrandTranslationRepository extends EntityRepository
{
    /*public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BrandTranslation::class);
    }*/

    // /**
    //  * @return BrandTranslation[] Returns an array of BrandTranslation objects
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
    public function findOneBySomeField($value): ?BrandTranslation
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
