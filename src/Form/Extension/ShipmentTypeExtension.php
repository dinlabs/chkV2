<?php

declare(strict_types=1);

namespace App\Form\Extension;

use Sylius\Bundle\CoreBundle\Form\Type\Checkout\ShipmentType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class ShipmentTypeExtension extends AbstractTypeExtension
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('allow_extra_fields', true);
    }

    public static function getExtendedTypes(): iterable
    {
        return [ShipmentType::class];
    }
}