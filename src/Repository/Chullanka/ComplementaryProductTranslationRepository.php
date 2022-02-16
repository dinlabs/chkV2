<?php

namespace App\Repository\Chullanka;

use App\Entity\Chullanka\ComplementaryProductTranslation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;

/**
 * @method ComplementaryProductTranslation|null find($id, $lockMode = null, $lockVersion = null)
 * @method ComplementaryProductTranslation|null findOneBy(array $criteria, array $orderBy = null)
 * @method ComplementaryProductTranslation[]    findAll()
 * @method ComplementaryProductTranslation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ComplementaryProductTranslationRepository extends EntityRepository
{
    /*public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ComplementaryProductTranslation::class);
    }*/

    // /**
    //  * @return ComplementaryProductTranslation[] Returns an array of ComplementaryProductTranslation objects
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
    public function findOneBySomeField($value): ?ComplementaryProductTranslation
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
