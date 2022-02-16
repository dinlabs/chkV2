<?php

namespace App\Form\Type;

use App\Entity\Chullanka\BrandTranslation;
use BitBag\SyliusCmsPlugin\Form\Type\WysiwygType;
use Sylius\Bundle\ResourceBundle\Form\Type\AbstractResourceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BrandTranslationType extends AbstractResourceType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('introduction', WysiwygType::class, [
                'label' => 'Introduction',
                'required' => false
            ])
            ->add('description', WysiwygType::class, [
                'attr' => ['class' => 'tinymce'],
                'label' => 'Description',
                'required' => false
            ])
            ->add('advertising', WysiwygType::class, [
                'label' => 'Encart promo',
                'required' => false
            ])
        ;
    }

    /*public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => BrandTranslation::class,
        ]);
    }*/
}
