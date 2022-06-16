<?php

/*
 * This file was created by developers working at BitBag
 * Do you need more information about us and what we do? Visit our https://bitbag.io website!
 * We are hiring developers from all over the world. Join us and start your new, exciting adventure and become part of us: https://bitbag.io/career
*/

declare(strict_types=1);

namespace App\Overrides\SyliusElasticsearchPlugin\Context;

use App\Entity\Product\ProductOption;
use App\Entity\Product\ProductVariant;
use BitBag\SyliusElasticsearchPlugin\Context\ProductOptionsContextInterface;
use BitBag\SyliusElasticsearchPlugin\Context\TaxonContextInterface;
use BitBag\SyliusElasticsearchPlugin\Finder\ProductOptionsFinderInterface;
use BitBag\SyliusElasticsearchPlugin\Formatter\StringFormatterInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

final class ProductOptionsContext implements ProductOptionsContextInterface
{
    /** @var RequestStack */
    private $requestStack;

    /** @var TaxonContextInterface */
    private $taxonContext;

    /** @var ProductOptionsFinderInterface */
    private $optionsFinder;

    private EntityManagerInterface $em;

    private $stringFormatter;

    public function __construct(
        RequestStack $requestStack,
        TaxonContextInterface $taxonContext,
        ProductOptionsFinderInterface $optionsFinder,
        EntityManagerInterface $em,
        StringFormatterInterface $stringFormatter
    ) {
        $this->requestStack = $requestStack;
        $this->taxonContext = $taxonContext;
        $this->optionsFinder = $optionsFinder;
        $this->em = $em;
        $this->stringFormatter = $stringFormatter;
    }

    public function getOptions(): ?array
    {
        $options = [];

        if($this->requestStack->getCurrentRequest()->get('slug'))
        {
            $taxon = $this->taxonContext->getTaxon();
            $options = $this->optionsFinder->findByTaxon($taxon);
        }

        if($this->requestStack->getCurrentRequest()->get('code'))
        {
            $brandCode = $this->requestStack->getCurrentRequest()->get('code');
            $brandCode = str_replace('-', '', $brandCode);//cf. App\Chullanka\Brand::getEscode()
            $options = $this->optionsFinder->findByBrand($brandCode);
        }

        return $options;
    }

    public function getOptionValues($option): ?array
    {
        $optionValues = [];
        $optionValuesRaw = [];

        if ($this->requestStack->getCurrentRequest()->get('slug'))
        {
            $taxon = $this->taxonContext->getTaxon();
            $optionValuesRaw = $this->em->getRepository(ProductVariant::class)->findOptionValueByTaxon($option, $taxon);
        }

        if ($this->requestStack->getCurrentRequest()->get('code'))
        {
            $brandCode = $this->requestStack->getCurrentRequest()->get('code');
            $optionValuesRaw = $this->em->getRepository(ProductVariant::class)->findOptionValueByBrand($option, $brandCode);
        }

        foreach ($optionValuesRaw as $optionValue) {
            $optionValues[$optionValue] = $this->stringFormatter->formatToLowercaseWithoutSpaces($optionValue);
        }

        return $optionValues;
    }
}
