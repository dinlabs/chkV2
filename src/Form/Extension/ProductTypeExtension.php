<?php

declare(strict_types=1);

namespace App\Form\Extension;

use App\Form\Type\ChulltestType;
use Sylius\Bundle\ProductBundle\Form\Type\ProductType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;

class ProductTypeExtension extends AbstractTypeExtension
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $object = $builder->getData();
        $builder->add('chulltest', ChulltestType::class, [
            'object' => $object,
            'label' => false
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public static function getExtendedTypes(): iterable
    {
        return [
            ProductType::class,
        ];
    }
}
