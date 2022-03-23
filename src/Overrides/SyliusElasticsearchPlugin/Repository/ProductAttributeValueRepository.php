<?php

declare(strict_types=1);

namespace App\Overrides\SyliusElasticsearchPlugin\Repository;

use App\Entity\Chullanka\Brand;
use App\Entity\Product\ProductTaxon;
use App\Entity\Taxonomy\Taxon;
use App\Entity\Taxonomy\TaxonTranslation;
use BitBag\SyliusElasticsearchPlugin\Repository\ProductAttributeValueRepositoryInterface;
use Doctrine\ORM\Query\Expr\Join;
use Sylius\Component\Attribute\Model\AttributeInterface;
use Sylius\Component\Product\Repository\ProductAttributeValueRepositoryInterface as BaseAttributeValueRepositoryInterface;

class ProductAttributeValueRepository implements ProductAttributeValueRepositoryInterface
{
    /** @var BaseAttributeValueRepositoryInterface */
    private $baseAttributeValueRepository;

    public function __construct(BaseAttributeValueRepositoryInterface $baseAttributeValueRepository)
    {
        $this->baseAttributeValueRepository = $baseAttributeValueRepository;
    }

    public function getUniqueAttributeValues(AttributeInterface $productAttribute): array
    {
        $queryBuilder = $this->baseAttributeValueRepository->createQueryBuilder('o');

        /** @var string|null $storageType */
        $storageType = $productAttribute->getStorageType();

        return $queryBuilder
            ->join('o.subject', 'p', 'WITH', 'p.enabled = 1')
            ->select('o.localeCode, o.' . $storageType . ' as value')
            ->where('o.attribute = :attribute')
            ->groupBy('o.' . $storageType)
            ->addGroupBy('o.localeCode')
            ->setParameter(':attribute', $productAttribute)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * Limite les valeurs d'attributs du taxon
     */
    public function getUniqueAttributeValuesByTaxon(AttributeInterface $productAttribute, string $taxonSlug): array
    {
        $queryBuilder = $this->baseAttributeValueRepository->createQueryBuilder('o');

        /** @var string|null $storageType */
        $storageType = $productAttribute->getStorageType();
        
        return $queryBuilder
            ->join('o.subject', 'p', 'WITH', 'p.enabled = 1')
            ->leftJoin(ProductTaxon::class, 'pt', Join::WITH, 'pt.product = p.id')
            ->leftJoin(Taxon::class, 't', Join::WITH, 'pt.taxon = t.id')
            ->leftJoin(TaxonTranslation::class, 'tt', Join::WITH, 'tt.translatable = t.id')
            ->select('o.localeCode, o.' . $storageType . ' as value')
            ->where('o.attribute = :attribute')
            ->andWhere('tt.slug = :taxon')
            ->groupBy('o.' . $storageType)
            ->addGroupBy('o.localeCode')
            ->setParameter(':attribute', $productAttribute)
            ->setParameter(':taxon', $taxonSlug)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * Limite les valeurs d'attributs de la marque
     */
    public function getUniqueAttributeValuesByBrand(AttributeInterface $productAttribute, string $brand): array
    {
        $queryBuilder = $this->baseAttributeValueRepository->createQueryBuilder('o');

        /** @var string|null $storageType */
        $storageType = $productAttribute->getStorageType();
        
        return $queryBuilder
            ->join('o.subject', 'p', 'WITH', 'p.enabled = 1')
            ->leftJoin(Brand::class, 'b', Join::WITH, 'p.brand = b.id')
            ->select('o.localeCode, o.' . $storageType . ' as value')
            ->where('o.attribute = :attribute')
            ->andWhere('b.code = :brand')
            ->groupBy('o.' . $storageType)
            ->addGroupBy('o.localeCode')
            ->setParameter(':attribute', $productAttribute)
            ->setParameter(':brand', $brand)
            ->getQuery()
            ->getResult()
        ;
    }
}
