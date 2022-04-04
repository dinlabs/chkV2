<?php

namespace App\Form\Type;

use App\Entity\Chullanka\Store;
use Sylius\Bundle\ProductBundle\Form\Type\ProductAutocompleteChoiceType;
use Sylius\Bundle\ResourceBundle\Form\Type\AbstractResourceType;
use Sylius\Bundle\ResourceBundle\Form\Type\ResourceTranslationsType;
use Sylius\Bundle\TaxonomyBundle\Form\Type\TaxonAutocompleteChoiceType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class StoreType extends AbstractResourceType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('enabled', CheckboxType::class, [
                'label' => 'app.store.enabled'
            ])
            ->add('warehouse', CheckboxType::class, [
                'label' => 'app.store.warehouse'
            ])
            ->add('name', TextType::class, [
                'label' => 'app.store.name'
            ])
            ->add('code', TextType::class, [
                'label' => 'Code'
            ])
            ->add('street', TextType::class, [
                'label' => 'app.store.street'
            ])
            ->add('postcode', TextType::class, [
                'label' => 'app.store.postcode'
            ])
            ->add('city', TextType::class, [
                'label' => 'app.store.city'
            ])
            ->add('latitude', TextType::class, [
                'label' => 'app.store.latitude',
                'required' => false
            ])
            ->add('longitude', TextType::class, [
                'label' => 'app.store.longitude',
                'required' => false
            ])
            ->add('phone_number', TextType::class, [
                'label' => 'app.store.phone_number',
                'required' => false
            ])
            ->add('email', TextType::class, [
                'label' => 'app.store.email',
                'required' => false
            ])
            ->add('background', TextType::class, [
                'label' => 'Image de fond',
                'required' => false,
                'disabled' => true
            ])
            ->add('background_file', FileType::class, [
                'label' => 'Télécharger une nouvelle image de fond (si besoin)',
                'required' => false,
            ])
            ->add('translations', ResourceTranslationsType::class, [
                'entry_type' => StoreTranslationType::class,
            ])
            ->add('exclusive_products', ProductAutocompleteChoiceType::class, [
                'label' => 'Produits en exclus',
                'multiple' => true,
            ])
            ->add('other_products', ProductAutocompleteChoiceType::class, [
                'label' => 'Les produits du magasin',
                'multiple' => true,
            ])
            ->add('taxons', TaxonAutocompleteChoiceType::class, [
                'label' => 'Catégories à afficher',
                'multiple' => true
            ])
            ->addEventListener(FormEvents::PRE_SET_DATA, function(FormEvent $event) {
                $entity = $event->getData();
                if(!is_null($entity->getCode()))
                {
                    $form = $event->getForm();
                    $form->add('code', TextType::class, [
                        'label' => 'Code',
                        'disabled' => true
                    ]);
                }
            })
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Store::class,
        ]);
    }
}
