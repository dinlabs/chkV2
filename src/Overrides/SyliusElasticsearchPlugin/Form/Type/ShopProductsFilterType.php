<?php

declare(strict_types=1);

namespace App\Overrides\SyliusElasticsearchPlugin\Form\Type;

use BitBag\SyliusElasticsearchPlugin\Form\Type\AbstractFilterType;
use BitBag\SyliusElasticsearchPlugin\Form\Type\ProductAttributesFilterType;
use BitBag\SyliusElasticsearchPlugin\Form\Type\PriceFilterType;
use Symfony\Component\Form\FormBuilderInterface;

final class ShopProductsFilterType extends AbstractFilterType
{
    /** @var string */
    private $namePropertyPrefix;

    public function __construct(string $namePropertyPrefix = 'name')
    {
        $this->namePropertyPrefix = $namePropertyPrefix;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            //->add($this->namePropertyPrefix, NameFilterType::class)
            //->add('options', ProductOptionsFilterType::class, ['required' => false, 'label' => false])
            ->add('attributes', ProductAttributesFilterType::class, ['required' => false, 'label' => false])
            //->add('price', PriceFilterType::class, ['required' => false, 'label' => false])
        ;
    }
}
