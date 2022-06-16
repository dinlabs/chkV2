<?php

declare(strict_types=1);

namespace App\Overrides\SyliusElasticsearchPlugin\PropertyBuilder;

use App\Repository\Chullanka\BrandRepository;
use BitBag\SyliusElasticsearchPlugin\PropertyBuilder\AbstractBuilder;
use BitBag\SyliusElasticsearchPlugin\Repository\TaxonRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Elastica\Document;
use Elastica\Index;
use FOS\ElasticaBundle\Event\PostTransformEvent;
use Sylius\Component\Product\Model\ProductOptionInterface;

final class OptionTaxonsBuilder extends AbstractBuilder
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
        EntityManagerInterface $em,
        string $taxonsProperty = 'option_taxons',
        array $excludedAttributes = [],
    ) {
        $this->taxonRepository = $taxonRepository;
        $this->taxonsProperty = $taxonsProperty;
        $this->excludedAttributes = $excludedAttributes;
        $this->brandRepository = $brandRepository;
        $this->em = $em;
    }

    public function consumeEvent(PostTransformEvent $event): void
    {
        $documentAttribute = $event->getObject();

        if (!$documentAttribute instanceof ProductOptionInterface
            || in_array($documentAttribute->getCode(), $this->excludedAttributes)
        ) {
            return;
        }

        $document = $event->getDocument();
        $taxons = $this->taxonRepository->getTaxonsByOptionViaProduct($documentAttribute);
        $taxonCodes = [];

        foreach ($taxons as $taxon) {
            $taxonCodes[] = str_replace('-', '', $taxon->getCode());
        }

        $brands = $this->brandRepository->getBrandsByOptionViaProduct($documentAttribute);
        $brandCodes = [];

        foreach($brands as $brand) {
            $brandCodes[] = $brand->getEscode();
        }

        $document->set($this->taxonsProperty, $taxonCodes);
        $document->set('option_brands', $brandCodes);
    }
}
