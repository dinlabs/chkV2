<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\GreaterThan;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;

class ChullankaRulesConfigurationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /* $builder->add('volume', NumberType::class, [
            'label' => 'app.form.total_volume_less_than_or_equal_configuration.volume',
            'constraints' => [
                new NotBlank(['groups' => ['sylius']]),
                new Type(['type' => 'numeric', 'groups' => ['sylius']]),
                new GreaterThan(['value' => 0, 'groups' => ['sylius']])
            ],
        ]); */
    }

    public function getBlockPrefix()
    {
        return 'app_shipping_method_rule_chullanka_rules_configuration';
    }
}