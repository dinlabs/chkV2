<?php

declare(strict_types=1);

namespace App\Overrides\SyliusElasticsearchPlugin\Controller\RequestDataHandler;

use BitBag\SyliusElasticsearchPlugin\Controller\RequestDataHandler\DataHandlerInterface;
use BitBag\SyliusElasticsearchPlugin\Exception\TaxonNotFoundException;
use BitBag\SyliusElasticsearchPlugin\Finder\ProductAttributesFinderInterface;
use Sylius\Component\Attribute\AttributeType\CheckboxAttributeType;
use Sylius\Component\Attribute\AttributeType\IntegerAttributeType;
use Sylius\Component\Locale\Context\LocaleContextInterface;
use Sylius\Component\Product\Model\ProductAttribute;
use Sylius\Component\Taxonomy\Repository\TaxonRepositoryInterface;

final class ShopProductListDataHandler implements DataHandlerInterface
{
    /** @var TaxonRepositoryInterface */
    private $taxonRepository;

    /** @var LocaleContextInterface */
    private $localeContext;

    /** @var ProductAttributesFinderInterface */
    private $attributesFinder;

    /** @var string */
    private $namePropertyPrefix;

    /** @var string */
    private $taxonsProperty;

    /** @var string */
    private $optionPropertyPrefix;

    /** @var string */
    private $attributePropertyPrefix;

    public function __construct(
        TaxonRepositoryInterface $taxonRepository,
        LocaleContextInterface $localeContext,
        ProductAttributesFinderInterface $attributesFinder,
        string $namePropertyPrefix = 'name',
        string $taxonsProperty = 'product_taxons',
        string $optionPropertyPrefix = 'option',
        string $attributePropertyPrefix = 'attribute'
    ) {
        $this->taxonRepository = $taxonRepository;
        $this->localeContext = $localeContext;
        $this->attributesFinder = $attributesFinder;
        $this->namePropertyPrefix = $namePropertyPrefix;
        $this->taxonsProperty = $taxonsProperty;
        $this->optionPropertyPrefix = $optionPropertyPrefix;
        $this->attributePropertyPrefix = $attributePropertyPrefix;
    }

    public function retrieveData(array $requestData): array
    {
        $data[$this->namePropertyPrefix] = (string) $requestData[$this->namePropertyPrefix];

        if($slug = $requestData['slug'])
        {
            $taxon = $this->taxonRepository->findOneBySlug($slug, $this->localeContext->getLocaleCode());
    
            /*if (null === $taxon) {
                throw new TaxonNotFoundException();
            }*/
            $data[$this->taxonsProperty] = strtolower($taxon->getCode());
            $data['taxon'] = $taxon;
            $attributesDefinitions = $this->attributesFinder->findByTaxon($taxon);
        }

        if(isset($requestData['price']))
        {
            $data = array_merge($data, $requestData['price']);
        }

        if(isset($requestData['brand']))
        {
            $brandCode = $requestData['brand'];
            $attributesDefinitions = $this->attributesFinder->findByBrand($brandCode);
            $data['brand'] = [ $brandCode ];
        }

        if (isset($requestData['availabilities']['availabilities']) && count($requestData['availabilities']['availabilities']) > 0) {
            $data['availabilities'] = $requestData['availabilities']['availabilities'];
            unset($requestData['availabilities']);
        }

        if (isset($requestData['brands']['brands']) && count($requestData['brands']['brands']) > 0) {
            $data['brand'] = $requestData['brands']['brands'];
            unset($requestData['brands']);
        }

        if (isset($requestData['promotions']['promotions']) && count($requestData['promotions']['promotions']) > 0) {
            $data['promotion'] = true;
            unset($requestData['promotions']);
        }

        $this->handleOptionsPrefixedProperty($requestData, $data);
        $this->handleAttributesPrefixedProperty($requestData, $data, $attributesDefinitions);

        return $data;
    }

    private function handleOptionsPrefixedProperty(
        array $requestData,
        array &$data
    ): void {
        if (!isset($requestData['options'])) {
            return;
        }

        foreach ($requestData['options'] as $key => $value) {
            if (is_array($value) && 0 === strpos($key, $this->optionPropertyPrefix)) {
                $data[$key] = array_map(function (string $property): string {
                    return strtolower($property);
                }, $value);
            }
        }
    }

    private function handleAttributesPrefixedProperty(
        array $requestData,
        array &$data,
        ?array $attributesDefinitions = []
    ): void {
        if (!isset($requestData['attributes'])) {
            return;
        }

        $attributeTypes = $this->getAttributeTypes($attributesDefinitions);

        foreach ($requestData['attributes'] as $key => $value) {
            if (!is_array($value) || 0 !== strpos($key, $this->attributePropertyPrefix)) {
                continue;
            }
            $data[$key] = $this->reformatAttributeArrayValues($value, $key, $attributeTypes);
        }
    }

    private function getAttributeTypes(array $attributesDefinitions): array
    {
        $data = [];
        /** @var ProductAttribute $attributesDefinition */
        foreach ($attributesDefinitions as $attributesDefinition) {
            $data['attribute_' . $attributesDefinition->getCode()] = $attributesDefinition->getType();
        }

        return $data;
    }

    private function reformatAttributeArrayValues(
        array $attributeValues,
        string $property,
        array $attributesDefinitions
    ): array
    {
        $reformattedValues = [];
        foreach ($attributeValues as $attributeValue) {
            switch ($attributesDefinitions[$property]) {
                case CheckboxAttributeType::TYPE:
                    $value = (bool) ($attributeValue);

                    break;
                case IntegerAttributeType::TYPE:
                    $value = (float) ($attributeValue);

                    break;
                default:
                    $value = strtolower($attributeValue);
            }
            $reformattedValues[] = $value;
        }

        return $reformattedValues;
    }
}
