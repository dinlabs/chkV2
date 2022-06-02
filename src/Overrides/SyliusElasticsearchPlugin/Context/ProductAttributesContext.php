<?php
declare(strict_types=1);

namespace App\Overrides\SyliusElasticsearchPlugin\Context;

use App\Entity\Chullanka\Store;
use App\Overrides\SyliusElasticsearchPlugin\Finder\ShopProductsFinder;
use BitBag\SyliusElasticsearchPlugin\Context\ProductAttributesContextInterface;
use BitBag\SyliusElasticsearchPlugin\Context\TaxonContextInterface;
use BitBag\SyliusElasticsearchPlugin\Finder\ProductAttributesFinderInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

final class ProductAttributesContext implements ProductAttributesContextInterface
{
    /** @var RequestStack */
    private $requestStack;

    /** @var TaxonContextInterface */
    private $taxonContext;

    /** @var ProductAttributesFinderInterface */
    private $attributesFinder;

    private $shopProductsFinder;

    private $em;

    public function __construct(
        RequestStack $requestStack,
        TaxonContextInterface $taxonContext,
        ProductAttributesFinderInterface $attributesFinder,
        ShopProductsFinder $shopProductsFinder,
        EntityManagerInterface $em
    ) {
        $this->requestStack = $requestStack;
        $this->taxonContext = $taxonContext;
        $this->attributesFinder = $attributesFinder;
        $this->shopProductsFinder = $shopProductsFinder;
        $this->em = $em;
    }

    private function getAvailabilitiesChoices()
    {
        $stores = $this->em->getRepository(Store::class)->findByEnabled(true);
        $choices = ['web' => 'Livraison à domicile / en point-relais'];

        foreach ($stores as $store) {
            $choices[$store->getCode()] = $store->getName();
        }

        return $choices;
    }

    public function getAvailabilities(): ?array
    {
        $availabilities = [];

        if($this->requestStack->getCurrentRequest()->get('slug'))
        {
            $taxon = $this->taxonContext->getTaxon();
            $availabilities = $this->shopProductsFinder->findAvailabilitiesByTaxon(
                $this->getAvailabilitiesChoices(),
                $taxon
            );
        }

        if($this->requestStack->getCurrentRequest()->get('code'))
        {
            $brandCode = $this->requestStack->getCurrentRequest()->get('code');
            $brandCode = str_replace('-', '', $brandCode);//cf. App\Chullanka\Brand::getEscode()
            $availabilities = $this->shopProductsFinder->findAvailabilitiesByBrand(
                $this->getAvailabilitiesChoices(),
                $brandCode
            );
        }


        return $availabilities;
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
    }
}
