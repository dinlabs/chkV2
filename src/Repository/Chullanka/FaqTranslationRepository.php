<?php

namespace App\Repository\Chullanka;

use App\Entity\Chullanka\FaqTranslation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;

/**
 * @method FaqTranslation|null find($id, $lockMode = null, $lockVersion = null)
 * @method FaqTranslation|null findOneBy(array $criteria, array $orderBy = null)
 * @method FaqTranslation[]    findAll()
 * @method FaqTranslation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FaqTranslationRepository extends EntityRepository
{
    /*public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FaqTranslation::class);
    }*/

    // /**
    //  * @return FaqTranslation[] Returns an array of FaqTranslation objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('f.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?FaqTranslation
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
