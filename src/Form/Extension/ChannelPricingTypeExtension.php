<?php
declare(strict_types=1);

namespace App\Form\Extension;

use Sylius\Bundle\CoreBundle\Form\Type\Product\ChannelPricingType;
use Symfony\Component\Form\AbstractTypeExtension;
use Sylius\Bundle\MoneyBundle\Form\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;

final class ChannelPricingTypeExtension extends AbstractTypeExtension
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // Adding new fields works just like in the parent form type.
            ->add('discountPrice', MoneyType::class, [
                'label' => 'Prix spécial',
                'required' => false,
                'currency' => $options['channel']->getBaseCurrency()->getCode(),
            ])
            ->add('discountFrom', DateType::class, [
                'label' => 'sylius.ui.start_date',
                'widget' => 'single_text',
            ])
            ->add('discountTo', DateType::class, [
                'label' => 'sylius.ui.end_date',
                'widget' => 'single_text',
            ])
        ;
    }
    
    public static function getExtendedTypes(): iterable
    {
        return [ChannelPricingType::class];
    }
}