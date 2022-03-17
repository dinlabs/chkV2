<?php

declare(strict_types=1);

namespace App\Overrides\SyliusElasticsearchPlugin\Finder;

use BitBag\SyliusElasticsearchPlugin\Controller\RequestDataHandler\PaginationDataHandlerInterface;
use BitBag\SyliusElasticsearchPlugin\Controller\RequestDataHandler\SortDataHandlerInterface;
use BitBag\SyliusElasticsearchPlugin\QueryBuilder\QueryBuilderInterface;
use BitBag\SyliusElasticsearchPlugin\Finder\ShopProductsFinderInterface;
use Elastica\Query;
use Elastica\Query\Terms;
use FOS\ElasticaBundle\Finder\PaginatedFinderInterface;
use Pagerfanta\Pagerfanta;

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

        $query = new Query($boolQuery);
        $query->addSort($data[SortDataHandlerInterface::SORT_INDEX]);

        $products = $this->productFinder->findPaginated($query);
        $products->setMaxPerPage($data[PaginationDataHandlerInterface::LIMIT_INDEX]);
        $products->setCurrentPage($data[PaginationDataHandlerInterface::PAGE_INDEX]);

        return $products;
    }
}
