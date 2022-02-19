<?php

namespace App\Repository;

use Loevgaard\SyliusBrandPlugin\Doctrine\ORM\ProductRepositoryInterface as LoevgaardSyliusBrandPluginProductRepositoryInterface;
use Loevgaard\SyliusBrandPlugin\Doctrine\ORM\ProductRepositoryTrait as LoevgaardSyliusBrandPluginProductRepositoryTrait;
use Sylius\Bundle\CoreBundle\Doctrine\ORM\ProductRepository as BaseProductRepository;

class ProductRepository extends BaseProductRepository implements LoevgaardSyliusBrandPluginProductRepositoryInterface
{
    public function findAllByBrand(Int $brand_id): array
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
    }
}