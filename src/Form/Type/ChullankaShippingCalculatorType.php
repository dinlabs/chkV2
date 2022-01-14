<?php

declare(strict_types=1);

namespace App\Form\Type;

use Sylius\Bundle\MoneyBundle\Form\Type\MoneyType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;

final class ChullankaShippingCalculatorType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('price', MoneyType::class, [
                'label' => 'app.calculator.chullanka.price',
                'currency' => 'EUR',
            ])
            ->add('sup_for_corsica', MoneyType::class, [
                'label' => 'app.calculator.chullanka.sup_for_corsica',
                'currency' => 'EUR',
            ])
            ->add('sup_outside_france', MoneyType::class, [
                'label' => 'app.calculator.chullanka.sup_outside_france',
                'currency' => 'EUR',
            ])
            ->add('free_above', MoneyType::class, [
                'label' => 'app.calculator.chullanka.free_above',
                'currency' => 'EUR',
            ])
        ;
    }
}