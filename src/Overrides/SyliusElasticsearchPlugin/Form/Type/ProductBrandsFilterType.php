<?php

/*
 * This file was created by developers working at BitBag
 * Do you need more information about us and what we do? Visit our https://bitbag.io website!
 * We are hiring developers from all over the world. Join us and start your new, exciting adventure and become part of us: https://bitbag.io/career
*/

declare(strict_types=1);

namespace App\Overrides\SyliusElasticsearchPlugin\Form\Type;

use BitBag\SyliusElasticsearchPlugin\Context\ProductAttributesContextInterface;
use BitBag\SyliusElasticsearchPlugin\Form\Type\AbstractFilterType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;

final class ProductBrandsFilterType extends AbstractFilterType
{
    /** @var ProductAttributesContextInterface */
    private $productAttributesContext;

    public function __construct(
        ProductAttributesContextInterface $productAttributesContext
    ) {
        $this->productAttributesContext = $productAttributesContext;
    }

    public function buildForm(FormBuilderInterface $builder, array $attributes): void
    {
        $brands = $this->productAttributesContext->getBrands();

        if (count($brands) > 0) {
            $builder->add('brands', ChoiceType::class, [
                'label' => 'Marques',
                'required' => false,
                'multiple' => true,
                'expanded' => true,
                'choices' => $brands,
                'attr' => ['preopened' => true]
            ]);
        }
    }
}
