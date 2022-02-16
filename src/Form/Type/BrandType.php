<?php

namespace App\Form\Type;

use App\Entity\Chullanka\Brand;
use Liip\ImagineBundle\Form\Type\ImageType;
use Sylius\Bundle\ProductBundle\Form\Type\ProductAutocompleteChoiceType;
use Sylius\Bundle\ResourceBundle\Form\Type\AbstractResourceType;
use Sylius\Bundle\ResourceBundle\Form\Type\ResourceTranslationsType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BrandType extends AbstractResourceType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'app.store.name'
            ])
            ->add('code', TextType::class, [
                'label' => 'Code'
            ])
            ->add('logo', TextType::class, [
                'label' => 'Logo',
                'required' => false,
                'disabled' => true
            ])
            ->add('logo_file', FileType::class, [
                'label' => 'Télécharger un nouveau logo (si besoin)',
                'required' => false,
            ])
            ->add('top', CheckboxType::class, [
                'label' => "Top marque sur la page d'accueil ?"
            ])
            ->add('top_position', IntegerType::class, [
                'label' => "Position dans la liste",
                'required' => false,
            ])
            ->add('translations', ResourceTranslationsType::class, [
                'entry_type' => BrandTranslationType::class
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
            ->add('top_product', ProductAutocompleteChoiceType::class, [
                'label' => 'Produit mis en avant',
            ])
            ->add('product_background', TextType::class, [
                'label' => 'Image de fond du produit mis en avant',
                'required' => false,
                'disabled' => true
            ])
            ->add('product_background_file', FileType::class, [
                'label' => 'Télécharger une nouvelle image de fond de produit (si besoin)',
                'required' => false,
            ])
            ->add('size_guide', TextType::class, [
                'label' => 'Guide des tailles',
                'required' => false
            ])
            ->add('soc_facebook', TextType::class, [
                'label' => 'URL de la page Facebook',
                'required' => false
            ])
            ->add('soc_twitter', TextType::class, [
                'label' => 'URL de la page Twitter',
                'required' => false
            ])
            ->add('soc_youtube', TextType::class, [
                'label' => 'URL de la page YouTube',
                'required' => false
            ])
            ->add('soc_pinterest', TextType::class, [
                'label' => 'URL de la page Pinterset',
                'required' => false
            ])
            ->add('soc_instagram', TextType::class, [
                'label' => 'URL de la page Instagram',
                'required' => false
            ])
            ->add('tag_instagram', TextType::class, [
                'label' => 'Tag Instagram',
                'required' => false
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
            'data_class' => Brand::class,
        ]);
    }
}
