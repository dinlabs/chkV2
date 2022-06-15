<?php

namespace App\Repository\Chullanka;

use App\Entity\Chullanka\Parameter;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;

/**
 * @method Parameter|null find($id, $lockMode = null, $lockVersion = null)
 * @method Parameter|null findOneBy(array $criteria, array $orderBy = null)
 * @method Parameter[]    findAll()
 * @method Parameter[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ParameterRepository extends EntityRepository
{
    /*public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Parameter::class);
    }*/

    public function getValue($slug): ?string
    {
        $result = $this->createQueryBuilder('p')
            ->andWhere('p.slug = :slug')
            ->setParameter('slug', $slug)
            ->getQuery()
            ->getOneOrNullResult()
        ;
        return is_null($result) ? null : $result->getValue();
    }

    public function getCartsGuruAuths()
    {
        $slugs = [
            'cartsguru-site-id',
            'cartsguru-auth-key'
        ];

        $result = $this->createQueryBuilder('p')
            ->andWhere('p.slug IN (:slugs)')
            ->setParameter('slugs', $slugs)
            ->getQuery()
            ->getArrayResult()
        ;

        return array_column($result, 'value', 'slug');
    }

    public function getCartsGuruSiteId()
    {
        try {
            $slug = 'cartsguru-site-id';
            $result = $this->createQueryBuilder('p')
                ->select('p.value')
                ->andWhere('p.slug = :slug')
                ->setParameter('slug', $slug)
                ->getQuery()
                ->getSingleScalarResult()
            ;

            return $result;
        } catch (\Exception $e) {
            return null;
        }
    }

    // /**
    //  * @return Parameter[] Returns an array of Parameter objects
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
    public function findOneBySomeField($value): ?Parameter
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
