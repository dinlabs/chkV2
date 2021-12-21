<?php

namespace App\Repository\Chullanka;

use App\Entity\Chullanka\ChulltestTranslation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;

/**
 * @method ChulltestTranslation|null find($id, $lockMode = null, $lockVersion = null)
 * @method ChulltestTranslation|null findOneBy(array $criteria, array $orderBy = null)
 * @method ChulltestTranslation[]    findAll()
 * @method ChulltestTranslation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ChulltestTranslationRepository extends EntityRepository
{
    /*public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ChulltestTranslation::class);
    }*/

    // /**
    //  * @return ChulltestTranslation[] Returns an array of ChulltestTranslation objects
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
    public function findOneBySomeField($value): ?ChulltestTranslation
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
