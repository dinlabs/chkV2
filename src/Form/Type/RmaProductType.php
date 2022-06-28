<?php

namespace App\Form\Type;

use App\Entity\Chullanka\RmaProduct;
use Sylius\Bundle\ResourceBundle\Form\Type\AbstractResourceType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RmaProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->addEventListener(FormEvents::PRE_SET_DATA, function(FormEvent $event) {
                $form = $event->getForm();
                $entity = $event->getData();
                $qty = ($entity instanceof RmaProduct) ? $entity->getOrderitem()->getQuantity() : 1;
                $form->add('quantity', ChoiceType::class, [
                    'label' => 'Quantité',
                    'choices' => range(0, $qty),
                ]);
            })
            ->add('reason', TextareaType::class, [
                'label' => 'Pour quelle raison ?'
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => RmaProduct::class,
        ]);
    }
}
