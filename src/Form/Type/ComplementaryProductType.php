<?php

namespace App\Form\Type;

use App\Entity\Chullanka\ComplementaryProduct;
use Sylius\Bundle\ProductBundle\Form\Type\ProductAutocompleteChoiceType;
use Sylius\Bundle\ResourceBundle\Form\Type\ResourceTranslationsType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ComplementaryProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('enabled', CheckboxType::class, [
                'label' => 'app.chulli.enabled'
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
            ->add('chulli')
            ->add('translations', ResourceTranslationsType::class, [
                'entry_type' => ComplementaryProductTranslationType::class
            ])
            ->add('products', ProductAutocompleteChoiceType::class, [
                'label' => 'bitbag_sylius_cms_plugin.ui.products',
                'multiple' => true,
            ])
            ->add('show_from', DateTimeType::class, [
                'label' => 'Afficher du',
                'date_widget' => 'single_text',
                'time_widget' => 'single_text',
                'required' => false,
            ])
            ->add('show_to', DateTimeType::class, [
                'label' => 'Jusqu\'au',
                'date_widget' => 'single_text',
                'time_widget' => 'single_text',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ComplementaryProduct::class,
            'object' => null
        ]);
    }
}
