<?php

declare(strict_types=1);

namespace App\Overrides\SyliusElasticsearchPlugin\PropertyBuilder;

use App\Repository\Chullanka\BrandRepository;
use BitBag\SyliusElasticsearchPlugin\PropertyBuilder\AbstractBuilder;
use BitBag\SyliusElasticsearchPlugin\Repository\TaxonRepositoryInterface;
use FOS\ElasticaBundle\Event\PostTransformEvent;
use Sylius\Component\Attribute\Model\AttributeInterface;

final class AttributeTaxonsBuilder extends AbstractBuilder
{
    /** @var TaxonRepositoryInterface */
    protected $taxonRepository;

    /** @var string */
    private $taxonsProperty;

    /** @var array */
    private $excludedAttributes;

    /** @var BrandRepository */
    protected $brandRepository;

    public function __construct(
        TaxonRepositoryInterface $taxonRepository,
        BrandRepository $brandRepository,
        string $taxonsProperty = 'attribute_taxons',
        array $excludedAttributes = [],
    ) {
        $this->taxonRepository = $taxonRepository;
        $this->taxonsProperty = $taxonsProperty;
        $this->excludedAttributes = $excludedAttributes;
        $this->brandRepository = $brandRepository;
    }

    public function consumeEvent(PostTransformEvent $event): void
    {
        $documentAttribute = $event->getObject();

        if (!$documentAttribute instanceof AttributeInterface
            || in_array($documentAttribute->getCode(), $this->excludedAttributes)
        ) {
            return;
        }

        $taxons = $this->taxonRepository->getTaxonsByAttributeViaProduct($documentAttribute);
        $taxonCodes = [];

        foreach ($taxons as $taxon) {
            $taxonCodes[] = $taxon->getCode();
        }

        $document = $event->getDocument();

        $document->set($this->taxonsProperty, $taxonCodes);

        // ajout Yannick pour les marques
        $brands = $this->brandRepository->getBrandsByAttributeViaProduct($documentAttribute);
        $brandCodes = [];
        foreach($brands as $brand)
        {
            $brandCodes[] = $brand->getEscode();
        }
        $document->set('attribute_brands', $brandCodes);
    }
}
