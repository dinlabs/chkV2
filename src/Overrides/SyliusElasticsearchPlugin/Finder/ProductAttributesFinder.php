<?php

declare(strict_types=1);

namespace App\Overrides\SyliusElasticsearchPlugin\Finder;

use BitBag\SyliusElasticsearchPlugin\Finder\ProductAttributesFinderInterface;
use BitBag\SyliusElasticsearchPlugin\QueryBuilder\QueryBuilderInterface;
use Elastica\Query\BoolQuery;
use Elastica\Query\Terms;
use FOS\ElasticaBundle\Finder\FinderInterface;
use Sylius\Component\Core\Model\TaxonInterface;

final class ProductAttributesFinder implements ProductAttributesFinderInterface
{
    /** @var FinderInterface */
    private $attributesFinder;

    /** @var QueryBuilderInterface */
    private $attributesByTaxonQueryBuilder;

    /** @var string */
    private $taxonsProperty;

    public function __construct(
        FinderInterface $attributesFinder,
        QueryBuilderInterface $attributesByTaxonQueryBuilder,
        string $taxonsProperty = 'attribute_taxons'
    ) {
        $this->attributesFinder = $attributesFinder;
        $this->attributesByTaxonQueryBuilder = $attributesByTaxonQueryBuilder;
        $this->taxonsProperty = $taxonsProperty;
    }

    public function findByTaxon(TaxonInterface $taxon): ?array
    {
        $data = [];
        $data[$this->taxonsProperty] = strtolower($taxon->getCode());

        $query = $this->attributesByTaxonQueryBuilder->buildQuery($data);

        return $this->attributesFinder->find($query, 20);
    }

    public function findByBrand($brandCode): ?array
    {
        $brandProperty = 'attribute_brands';
        
        $query = new BoolQuery();
        $brandQuery = new Terms($brandProperty);
        $brandQuery->setTerms([$brandCode]);

        $query->addMust($brandQuery);

        return $this->attributesFinder->find($query, 20);
    }
}
