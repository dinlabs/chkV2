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

    private $elasticaIndexBitbagOptionTaxons;

    public function __construct(
        TaxonRepositoryInterface $taxonRepository,
        BrandRepository $brandRepository,
        EntityManagerInterface $em,
        Index $elasticaIndexBitbagOptionTaxons,
        string $taxonsProperty = 'option_taxons',
        array $excludedAttributes = [],
    ) {
        $this->taxonRepository = $taxonRepository;
        $this->taxonsProperty = $taxonsProperty;
        $this->excludedAttributes = $excludedAttributes;
        $this->brandRepository = $brandRepository;
        $this->elasticaIndexBitbagOptionTaxons = $elasticaIndexBitbagOptionTaxons;
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

        $taxons = $this->taxonRepository->getTaxonsByOptionViaProduct($documentAttribute);
        $optionId = (string) $documentAttribute->getId();
        $taxonCodes = [];

        foreach ($taxons as $taxon) {
            $taxonCodes[] = str_replace('-', '', $taxon->getCode());
        }

        $brands = $this->brandRepository->getBrandsByOptionViaProduct($documentAttribute);
        $brandCodes = [];

        foreach($brands as $brand) {
            $brandCodes[] = $brand->getEscode();
        }

        $this->elasticaIndexBitbagOptionTaxons->addDocument(
            new Document($optionId, [
                $this->taxonsProperty => $taxonCodes,
                'option_brands' => $brandCodes
            ])
        );
    }
}
