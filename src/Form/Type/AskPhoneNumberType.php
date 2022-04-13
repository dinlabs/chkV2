<?php

namespace App\Form\Type;

use App\Entity\Customer\Customer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Regex;

class AskPhoneNumberType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('phoneNumber', TextType::class, [
                'constraints' => [
                    new Regex([
                        'pattern' => '/^0(6|7)[0-9]{8}$/',
                        'message' => 'Numéro non valide',
                    ]),
                ],
                'attr' => [
                    'placeholder' => 'Votre portable 06 ou 07',
                    'pattern' => '^0(6|7)[0-9]{8}$',
                    'title' => '10 chiffres, sans espace, commençant par 06 ou 07'
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Customer::class,
        ]);
    }
}
