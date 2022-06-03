<?php

declare(strict_types=1);

namespace App\Overrides\SyliusElasticsearchPlugin\Finder;

use BitBag\SyliusElasticsearchPlugin\Controller\RequestDataHandler\PaginationDataHandlerInterface;
use BitBag\SyliusElasticsearchPlugin\Controller\RequestDataHandler\SortDataHandlerInterface;
use BitBag\SyliusElasticsearchPlugin\QueryBuilder\QueryBuilderInterface;
use BitBag\SyliusElasticsearchPlugin\Finder\ShopProductsFinderInterface;
use Elastica\Query;
use Elastica\Query\BoolQuery;
use Elastica\Query\Range;
use Elastica\Query\Term;
use Elastica\Query\Terms;
use FOS\ElasticaBundle\Finder\PaginatedFinderInterface;
use Pagerfanta\Pagerfanta;
use Sylius\Component\Core\Model\TaxonInterface;

final class ShopProductsFinder implements ShopProductsFinderInterface
{
    /** @var QueryBuilderInterface */
    private $shopProductsQueryBuilder;

    /** @var PaginatedFinderInterface */
    private $productFinder;

    public function __construct(
        QueryBuilderInterface $shopProductsQueryBuilder,
        PaginatedFinderInterface $productFinder
    ) {
        $this->shopProductsQueryBuilder = $shopProductsQueryBuilder;
        $this->productFinder = $productFinder;
    }

    public function findAvailabilitiesByTaxon(array $storeNames, TaxonInterface $taxon): array
    {
        $availabilities = [];
        $storeCodes = array_keys($storeNames);

        foreach ($storeCodes as $storeCode) {
            $boolQuery = new BoolQuery();
            $rangeQuery = new Range('availabilities.'. $storeCode, ['gt' => 0]);
            $boolQuery->addMust($rangeQuery);

            $taxonQuery = new Terms('product_taxons');
            $taxonQuery->setTerms([$taxon->getCode()]);
            $boolQuery->addMust($taxonQuery);

            $query = new Query($boolQuery);
            $res = $this->productFinder->find($query, 1);

            if (count($res) > 0 && isset($storeNames[$storeCode])) {
                $availabilities[$storeNames[$storeCode]] = $storeCode;
            }
        }

        return $availabilities;
    }

    public function findBrandsByTaxon(TaxonInterface $taxon): array
    {
        $brands = [];
        $boolQuery = new BoolQuery();
        $taxonQuery = new Terms('product_taxons');
        $taxonQuery->setTerms([$taxon->getCode()]);
        $boolQuery->addMust($taxonQuery);

        $query = new Query($boolQuery);
        $products = $this->productFinder->find($query, 10000);

        foreach ($products as $product) {
            if ($product->getBrand() === null) {
                continue;
            }

            if (!in_array($product->getBrand()->getCode(), $brands)) {
                $brands[$product->getBrand()->getName()] = $product->getBrand()->getEscode();
            }
        }

        return $brands;
    }

    public function findAvailabilitiesByBrand(array $storeNames, string $brandCode): array
    {
        $availabilities = [];
        $storeCodes = array_keys($storeNames);

        foreach ($storeCodes as $storeCode) {
            $boolQuery = new BoolQuery();
            $rangeQuery = new Range('availabilities.'. $storeCode, ['gt' => 0]);
            $boolQuery->addMust($rangeQuery);

            $brandQuery = new Terms('brand');
            $brandQuery->setTerms([$brandCode]);
            $boolQuery->addMust($brandQuery);

            $query = new Query($boolQuery);
            $res = $this->productFinder->find($query, 1);

            if (count($res) > 0 && isset($storeNames[$storeCode])) {
                $availabilities[$storeNames[$storeCode]] = $storeCode;
            }
        }

        return $availabilities;
    }

    public function find(array $data): Pagerfanta
    {
        $boolQuery = $this->shopProductsQueryBuilder->buildQuery($data);

        $_key = 'brand';
        if(isset($data[ $_key ]))
        {
            $brandQuery = new Terms($_key);
            $brandQuery->setTerms($data[ $_key ]);
            if($brandQuery !== null)  $boolQuery->addMust($brandQuery);
        }

        if (isset($data['availabilities']) && count($data['availabilities']) > 0) {
            foreach ($data['availabilities'] as $availability) {
                $rangeQuery = new Range('availabilities.'. $availability, ['gt' => 0]);
                $boolQuery->addMust($rangeQuery);
            }
        }

        if (isset($data['promotion']) && $data['promotion'] === true) {
            $brandQuery = new Term();
            $brandQuery->setTerm('promotion', true);
            $boolQuery->addMust($brandQuery);
        }

        if (isset($data['new']) && $data['new'] === true) {
            $dateTime = new \DateTime();
            $dateTime->setTime(0, 0 ,0);
            $date = $dateTime->format('c');

            $rangeQuery = new Range('newFrom', ['lte' => $date]);
            $boolQuery->addMust($rangeQuery);

            $boolQueryNewToQuery = new BoolQuery();
            $boolQueryNewToQuery->setMinimumShouldMatch(1);

            $newToTimeQuery = new Range('newTo', ['gte' => $date]);
            $boolQueryNewToQuery->addShould($newToTimeQuery);

            $newToTimeEmptyQuery = new Term();
            $newToTimeEmptyQuery->setTerm('newToEmpty', true);
            $boolQueryNewToQuery->addShould($newToTimeEmptyQuery);

            $boolQuery->addMust($boolQueryNewToQuery);
        }

        $query = new Query($boolQuery);
        $query->addSort($data[SortDataHandlerInterface::SORT_INDEX]);

        $products = $this->productFinder->findPaginated($query);
        $products->setMaxPerPage($data[PaginationDataHandlerInterface::LIMIT_INDEX]);
        $products->setCurrentPage($data[PaginationDataHandlerInterface::PAGE_INDEX]);

        return $products;
    }
}
