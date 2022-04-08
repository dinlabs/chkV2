<?php

declare(strict_types=1);

namespace App\Overrides\SyliusElasticsearchPlugin\Controller\RequestDataHandler;

use BitBag\SyliusElasticsearchPlugin\Context\TaxonContextInterface;
use BitBag\SyliusElasticsearchPlugin\Controller\RequestDataHandler\SortDataHandlerInterface;
use BitBag\SyliusElasticsearchPlugin\PropertyNameResolver\ConcatedNameResolverInterface;
use Sylius\Component\Channel\Context\ChannelContextInterface;

final class ShopProductsSortDataHandler implements SortDataHandlerInterface
{
    /** @var ConcatedNameResolverInterface */
    private $channelPricingNameResolver;

    /** @var ChannelContextInterface */
    private $channelContext;

    /** @var TaxonContextInterface */
    private $taxonContext;

    /** @var ConcatedNameResolverInterface */
    private $taxonPositionNameResolver;

    /** @var string */
    private $soldUnitsProperty;

    /** @var string */
    private $createdAtProperty;

    /** @var string */
    private $pricePropertyPrefix;

    public function __construct(
        ConcatedNameResolverInterface $channelPricingNameResolver,
        ChannelContextInterface $channelContext,
        TaxonContextInterface $taxonContext,
        ConcatedNameResolverInterface $taxonPositionNameResolver,
        string $soldUnitsProperty = 'sold_units',
        string $createdAtProperty = 'product_created_at',
        string $pricePropertyPrefix = 'price'
    ) {
        $this->channelPricingNameResolver = $channelPricingNameResolver;
        $this->channelContext = $channelContext;
        $this->taxonContext = $taxonContext;
        $this->taxonPositionNameResolver = $taxonPositionNameResolver;
        $this->soldUnitsProperty = $soldUnitsProperty;
        $this->createdAtProperty = $createdAtProperty;
        $this->pricePropertyPrefix = $pricePropertyPrefix;
    }

    public function retrieveData(array $requestData): array
    {
        $data = [];

        //$orderBy = $requestData[self::ORDER_BY_INDEX] ?? $this->pricePropertyPrefix;
        $orderBy = '';
        $sort = '';
        $availableSorters = [];

        // sorting Target2Sell
        //if(true)
        if(isset($_COOKIE) && isset($_COOKIE['t2s-rank']))
        {
            $rank = $_COOKIE['t2s-rank'] ?: 'rank1';
            //$orderBy = $rank;
            $orderBy = 'attribute_' . $rank . '_fr_FR'; // hack!
            for($r=1; $r<=6; $r++)
            {
                //$availableSorters[] = 'rank' . $r;
                $availableSorters[] = 'attribute_rank' . $r . '_fr_FR';
            }
            $sort = self::SORT_DESC_INDEX;
        }
        // fin default

        if($requestData['slug'])
        {
            $positionSortingProperty = $this->getPositionSortingProperty();
            
            //$orderBy = $requestData[self::ORDER_BY_INDEX] ?? $positionSortingProperty;
            $orderBy = $requestData[self::ORDER_BY_INDEX] ?? ($orderBy ?: $positionSortingProperty);
            
            $availableSorters[] = $positionSortingProperty;
        }
        $availableSorters = array_merge($availableSorters, [$this->soldUnitsProperty, $this->createdAtProperty, $this->pricePropertyPrefix]);

        // au cas où...
        $orderBy = $orderBy ?: $this->pricePropertyPrefix;

        //error_log('ordreBy : '.$orderBy);

        //$sort = $requestData[self::SORT_INDEX] ?? self::SORT_ASC_INDEX;
        $sort = $requestData[self::SORT_INDEX] ?? ($sort ?: self::SORT_ASC_INDEX);
        $availableSorting = [self::SORT_ASC_INDEX, self::SORT_DESC_INDEX];

        if (!in_array($orderBy, $availableSorters) || !in_array($sort, $availableSorting)) {
            throw new \UnexpectedValueException();
        }

        if ($this->pricePropertyPrefix === $orderBy) {
            $channelCode = $this->channelContext->getChannel()->getCode();
            $orderBy = $this->channelPricingNameResolver->resolvePropertyName($channelCode);
        }

        $data['sort'] = [$orderBy => ['order' => strtolower($sort), 'unmapped_type' => 'keyword']];

        //error_log(print_r($data,true));
        return $data;
    }

    private function getPositionSortingProperty(): string
    {
        $taxonCode = $this->taxonContext->getTaxon()->getCode();

        return $this->taxonPositionNameResolver->resolvePropertyName($taxonCode);
    }
}
