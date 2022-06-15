<?php

namespace App\Repository\Chullanka;

use App\Entity\Chullanka\Brand;
use App\Entity\Product\Product;
use App\Entity\Product\ProductAttributeValue;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Sylius\Component\Attribute\Model\AttributeInterface;

/**
 * @method Brand|null find($id, $lockMode = null, $lockVersion = null)
 * @method Brand|null findOneBy(array $criteria, array $orderBy = null)
 * @method Brand[]    findAll()
 * @method Brand[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BrandRepository extends EntityRepository
{
    /*public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Brand::class);
    }*/
    
    public function findByPhrase(string $phrase): array
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.name LIKE :phrase OR o.code LIKE :phrase')
            ->setParameter('phrase', '%' . $phrase . '%')
            ->getQuery()
            ->getResult()
        ;
    }

    public function getTopBrands()
    {
        return $this->createQueryBuilder('b')
                    ->where('b.top = 1')
                    ->orderBy('b.top_position', 'ASC')
                    ->getQuery()
                    ->getResult()
        ;
    }

    public function getBrandsListByLetter()
    {
        $brandCollection = $this->createQueryBuilder('b')
            ->orderBy('b.name', 'ASC')
            ->getQuery()
            ->getResult()
        ;
        $brands = [];
        foreach($brandCollection as $_brand)
        {
            $firstLetter = substr($_brand->getName(), 0, 1);
            if(!ctype_alpha($firstLetter)) $firstLetter = '0-9';
            
            $brands[ strtoupper($firstLetter) ][] = $_brand;
        }
        return $brands;
    }

    
    public function getBrandsByAttributeViaProduct(AttributeInterface $attribute): array
    {
        return $this
            ->createQueryBuilder('b')
            ->distinct(true)
            ->select('b')
            ->leftJoin(Product::class, 'p', Join::WITH, 'b=p.brand')
            ->leftJoin(ProductAttributeValue::class, 'pav', Join::WITH, 'pav.subject = p.id')
            ->where('pav.attribute = :attribute')
            ->setParameter(':attribute', $attribute)
            ->getQuery()
            ->getResult()
        ;
    }

    public function getBrandsByOptionViaProduct($option): array
    {
        return $this
            ->createQueryBuilder('b')
            ->distinct(true)
            ->select('b')
            ->leftJoin(Product::class, 'p', Join::WITH, 'b=p.brand')
            ->join('p.variants', 'v')
            ->join('v.optionValues', 'ov')
            ->where('ov.option = :option')
            ->setParameter(':option', $option)
            ->getQuery()
            ->getResult()
        ;
    }

    // /**
    //  * @return Brand[] Returns an array of Brand objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('b.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Brand
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
