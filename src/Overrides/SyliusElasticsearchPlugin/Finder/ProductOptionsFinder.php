<?php

declare(strict_types=1);

namespace App\Overrides\SyliusElasticsearchPlugin\Finder;

use BitBag\SyliusElasticsearchPlugin\Finder\ProductOptionsFinderInterface;
use BitBag\SyliusElasticsearchPlugin\QueryBuilder\QueryBuilderInterface;
use Elastica\Query\BoolQuery;
use Elastica\Query\Terms;
use FOS\ElasticaBundle\Finder\FinderInterface;
use Sylius\Component\Core\Model\TaxonInterface;

final class ProductOptionsFinder implements ProductOptionsFinderInterface
{
    /** @var FinderInterface */
    private $fosElasticaFinderBitbagOptionTaxons;

    /** @var QueryBuilderInterface */
    private $productOptionsByTaxonQueryBuilder;

    /** @var string */
    private $taxonsProperty;

    // We must an high limit to retrieve all elements
    // https://github.com/FriendsOfSymfony/FOSElasticaBundle/issues/169
    private const LIMIT = 10000;

    public function __construct(
        FinderInterface $fosElasticaFinderBitbagOptionTaxons,
        QueryBuilderInterface $productOptionsByTaxonQueryBuilder,
        string $taxonsProperty = 'option_taxons'
    ) {
        $this->fosElasticaFinderBitbagOptionTaxons = $fosElasticaFinderBitbagOptionTaxons;
        $this->productOptionsByTaxonQueryBuilder = $productOptionsByTaxonQueryBuilder;
        $this->taxonsProperty = $taxonsProperty;
    }

    public function findByTaxon(TaxonInterface $taxon): ?array
    {
        $data = [];
        $data[$this->taxonsProperty] = strtolower($taxon->getCode());

        $query = $this->productOptionsByTaxonQueryBuilder->buildQuery($data);

        return $this->fosElasticaFinderBitbagOptionTaxons->find($query, self::LIMIT);
    }

    public function findByBrand($brandCode): ?array
    {
        $brandProperty = 'option_brands';
        
        $query = new BoolQuery();
        $brandQuery = new Terms($brandProperty);
        $brandQuery->setTerms([$brandCode]);

        $query->addMust($brandQuery);

        return $this->fosElasticaFinderBitbagOptionTaxons->find($query, self::LIMIT);
    }
}
