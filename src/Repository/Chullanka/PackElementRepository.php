<?php

namespace App\Repository\Chullanka;

use App\Entity\Chullanka\PackElement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method PackElement|null find($id, $lockMode = null, $lockVersion = null)
 * @method PackElement|null findOneBy(array $criteria, array $orderBy = null)
 * @method PackElement[]    findAll()
 * @method PackElement[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PackElementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PackElement::class);
    }

    // /**
    //  * @return PackElement[] Returns an array of PackElement objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('p.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?PackElement
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
