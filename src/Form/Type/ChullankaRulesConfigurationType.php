<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;

class ChullankaRulesConfigurationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('method', ChoiceType::class, [
                'label' => 'Type de méthode de livraison',
                'choices' => [
                    '' => '',
                    'A Domicile' => 'home',
                    'En Point-Relais' => 'pickup',
                    'En Magasin' => 'store',
                ]
            ])
            ->add('speed', ChoiceType::class, [
                'label' => 'Rapidité de livraison',
                'choices' => [
                    '' => '',
                    'Standard' => 'standart',
                    'Express' => 'express',
                ]
            ])
        ;
    }

    public function getBlockPrefix()
    {
        return 'app_shipping_method_rule_chullanka_rules_configuration';
    }
}