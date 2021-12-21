<?php

namespace App\Form\Type;

use App\Entity\Chullanka\Store;
use Sylius\Bundle\ResourceBundle\Form\Type\AbstractResourceType;
use Sylius\Bundle\ResourceBundle\Form\Type\ResourceTranslationsType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class StoreType extends AbstractResourceType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'app.store.name',
                'required' => false
            ])
            ->add('surface', TextType::class, [
                'label' => 'app.store.surface',
                'required' => false
            ])
            ->add('latitude', TextType::class, [
                'label' => 'app.store.latitude',
                'required' => false
            ])
            ->add('longitude', TextType::class, [
                'label' => 'app.store.longitude',
                'required' => false
            ])
            ->add('translations', ResourceTranslationsType::class, [
                'entry_type' => StoreTranslationType::class,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Store::class,
        ]);
    }
}
