<?php

declare(strict_types=1);

namespace App\Overrides\SyliusElasticsearchPlugin\Form\Type\ChoiceMapper;

use BitBag\SyliusElasticsearchPlugin\Form\Type\ChoiceMapper\ProductAttributesMapperInterface;
use BitBag\SyliusElasticsearchPlugin\Formatter\StringFormatterInterface;
use BitBag\SyliusElasticsearchPlugin\Repository\ProductAttributeValueRepositoryInterface;
use Sylius\Component\Attribute\AttributeType\SelectAttributeType;
use Sylius\Component\Locale\Context\LocaleContextInterface;
use Sylius\Component\Product\Model\ProductAttributeInterface;
use Symfony\Component\HttpFoundation\RequestStack;

final class ProductAttributesMapper implements ProductAttributesMapperInterface
{
    /** @var ProductAttributeValueRepositoryInterface */
    private $productAttributeValueRepository;

    /** @var LocaleContextInterface */
    private $localeContext;

    /** @var StringFormatterInterface */
    private $stringFormatter;

    /** @var RequestStack */
    private $requestStack;

    public function __construct(
        ProductAttributeValueRepositoryInterface $productAttributeValueRepository,
        LocaleContextInterface $localeContext,
        StringFormatterInterface $stringFormatter,
        RequestStack $requestStack
    ) {
        $this->productAttributeValueRepository = $productAttributeValueRepository;
        $this->localeContext = $localeContext;
        $this->stringFormatter = $stringFormatter;
        $this->requestStack = $requestStack;
    }

    public function mapToChoices(ProductAttributeInterface $productAttribute): array
    {
        //modif Yannick
        if($taxonSlug = $this->requestStack->getCurrentRequest()->get('slug'))
        {
            $attributeValues = $this->productAttributeValueRepository->getUniqueAttributeValuesByTaxon($productAttribute, $taxonSlug);
        }
        elseif($brand = $this->requestStack->getCurrentRequest()->get('code'))
        {
            $attributeValues = $this->productAttributeValueRepository->getUniqueAttributeValuesByBrand($productAttribute, $brand);
        }
        else 
            $attributeValues = $this->productAttributeValueRepository->getUniqueAttributeValues($productAttribute);

        $goodValues = [];
        foreach($attributeValues as $_att)
        {
            if(!empty($_att['value']))
            {
                if(is_array($_att['value']))
                {
                    foreach($_att['value'] as $v)
                    {
                        $goodValues[$v] = $v;
                    }
                }
            }
        }
        $goodValues = array_values($goodValues);

        $configuration = $productAttribute->getConfiguration();
        if (isset($configuration['choices']) && is_array($configuration['choices'])) {
            $choices = [];
            foreach ($configuration['choices'] as $singleValue => $val) {
                
                if(!in_array($singleValue, $goodValues)) continue;//ajout Yannick
                
                $label = $configuration['choices'][$singleValue][$this->localeContext->getLocaleCode()];
                $singleValue = SelectAttributeType::TYPE === $productAttribute->getType() ? $label : $singleValue;
                $choice = $this->stringFormatter->formatToLowercaseWithoutSpaces($singleValue);
                $choices[$label] = $choice;
            }

            return $choices;
        }
        
        $choices = [];
        array_walk($attributeValues, function ($productAttributeValue) use (&$choices, $productAttribute): void {
            $value = $productAttributeValue['value'];

            $configuration = $productAttribute->getConfiguration();

            if (is_array($value)
                && isset($configuration['choices'])
                && is_array($configuration['choices'])
            ) {
                foreach ($value as $singleValue) {
                    $choice = $this->stringFormatter->formatToLowercaseWithoutSpaces($singleValue);
                    $label = $configuration['choices'][$singleValue][$this->localeContext->getLocaleCode()];
                    $choices[$label] = $choice;
                }
            } else {
                $choice = is_string($value) ? $this->stringFormatter->formatToLowercaseWithoutSpaces($value) : $value;
                $choices[$value] = $choice;
            }
        });
        unset($attributeValues);

        return $choices;
    }
}
