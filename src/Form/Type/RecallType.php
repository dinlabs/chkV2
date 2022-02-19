<?php

namespace App\Form\Type;

use App\Entity\Chullanka\Recall;
use Sylius\Bundle\ResourceBundle\Form\Type\AbstractResourceType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RecallType extends AbstractResourceType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('state', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => [
                    'Nouveau' => 0,
                    'En cours' => 1,
                    'Clôturé' => 2
                ]
            ])
            //->add('phone_number')
            ->add('comment', TextareaType::class, [
                'label' => 'Commentaires',
                'required' => false
            ])
            //->add('createdAt')
            //->add('updatedAt')
            //->add('customer')
            //->add('product')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Recall::class,
        ]);
    }
}
