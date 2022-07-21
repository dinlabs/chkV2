<?php

namespace App\Repository;

use Sylius\Bundle\CoreBundle\Doctrine\ORM\ProductRepository as BaseProductRepository;

class ProductRepository extends BaseProductRepository
{
    public function findBySearch($term)
    {
        return $this->createQueryBuilder('o')
            ->select('translation.name')
            ->addSelect('o.code')
            ->innerJoin('o.translations', 'translation', 'WITH', 'translation.locale = :locale')
            ->addOrderBy('o.updatedAt', 'DESC')
            ->andWhere('o.code LIKE :term')
            ->orWhere('translation.name LIKE :term')
            ->setParameter('locale', 'fr_FR')
            ->setParameter('term', '%' . $term . '%')
            ->setMaxResults(20)
            ->getQuery()
            ->getResult();
    }

    public function findAllEnabled()
    {
        return $this->createQueryBuilder('o')
            ->where('o.enabled = :enabled')
            ->setParameter('enabled', true)
            ->orderBy('p.id', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /*public function findAllByBrand(Int $brand_id): array
    {
        $brand = $this->getEntityManager()
                        ->getRepository('Loevgaard\SyliusBrandPlugin\Model\Brand')
                        ->find($brand_id);

        return $this->createQueryBuilder('p')
                    ->where('p.brand = :brand')
                    ->setParameter('brand', $brand)
                    ->orderBy('p.id', 'ASC')
                    ->setMaxResults(10)
                    ->getQuery()
                    ->getResult()
        ;
    }*/
}