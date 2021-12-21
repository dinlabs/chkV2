<?php

namespace App\Form\Type;

use App\Entity\Chullanka\Chulltest;
use App\Entity\Product\Product;
use Sylius\Bundle\ResourceBundle\Form\Type\ResourceTranslationsType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\RangeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ChulltestType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('chulli')
            ->add('date', DateType::class, [
                'widget' => 'single_text',
                'label' => 'app.chull_test.date',
                'required' => false
                ])
            ->add('translations', ResourceTranslationsType::class, [
                'entry_type' => ChulltestTranslationType::class
            ])
            ->add('note', RangeType::class, [
                'attr' => [
                    'min' => 1,
                    'max' => 5
                ],
                'label' => 'app.chull_test.note',
                /*'choices'  => [
                    '5' => 5,
                    '4' => 4,
                    '3' => 3,
                    '2' => 2,
                    '1' => 1,
                ],*/
                'required' => false
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Chulltest::class,
            'object' => null
        ]);
    }
}
