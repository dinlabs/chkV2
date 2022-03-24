<?php
declare(strict_types=1);

namespace App\Overrides\SyliusElasticsearchPlugin\Context;

use BitBag\SyliusElasticsearchPlugin\Context\ProductAttributesContextInterface;
use BitBag\SyliusElasticsearchPlugin\Context\TaxonContextInterface;
use BitBag\SyliusElasticsearchPlugin\Finder\ProductAttributesFinderInterface;
use Symfony\Component\HttpFoundation\RequestStack;

final class ProductAttributesContext implements ProductAttributesContextInterface
{
    /** @var RequestStack */
    private $requestStack;

    /** @var TaxonContextInterface */
    private $taxonContext;

    /** @var ProductAttributesFinderInterface */
    private $attributesFinder;

    public function __construct(
        RequestStack $requestStack,
        TaxonContextInterface $taxonContext,
        ProductAttributesFinderInterface $attributesFinder,
    ) {
        $this->requestStack = $requestStack;
        $this->taxonContext = $taxonContext;
        $this->attributesFinder = $attributesFinder;
    }

    public function getAttributes(): ?array
    {
        $attributes = [];

        if($this->requestStack->getCurrentRequest()->get('slug'))
        {
            $taxon = $this->taxonContext->getTaxon();
            $attributes = $this->attributesFinder->findByTaxon($taxon);
        }

        if($this->requestStack->getCurrentRequest()->get('code'))
        {
            $brandCode = $this->requestStack->getCurrentRequest()->get('code');
            $brandCode = str_replace('-', '', $brandCode);//cf. App\Chullanka\Brand::getEscode()
            $attributes = $this->attributesFinder->findByBrand($brandCode);
        }

        // remove unwanted attributes
        $keepAttributes = [];
        for($a=0; $a<count($attributes); $a++)
        {
            $attr = $attributes[$a];
            $conf = $attr->getConfiguration();
            if(!$attr->getFilterable() || empty($conf['choices'])) continue;

            $keepAttributes[] = $attr;
        }
        return $keepAttributes;

        return $attributes;
    }
}
