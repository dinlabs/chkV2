<?php

declare(strict_types=1);

namespace App\Form\Extension;

use Sylius\Bundle\ProductBundle\Form\Type\ProductAutocompleteChoiceType;
use Sylius\Bundle\TaxonomyBundle\Form\Type\TaxonAutocompleteChoiceType;
use Sylius\Bundle\TaxonomyBundle\Form\Type\TaxonType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class TaxonTypeExtension extends AbstractTypeExtension
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('top_brands');
        $builder->add('top_products', ProductAutocompleteChoiceType::class, [
            'label' => 'Top Produits',
            'multiple' => true,
        ]);
        $builder->add('other_taxons', TaxonAutocompleteChoiceType::class, [
            'label' => 'Si vous regardiez aussi',
            'multiple' => true,
        ]);
        $builder->add('blogfeedurl', TextType::class, [
            'label' => 'Url du flux RSS pour cette catégorie',
            'required' => false
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public static function getExtendedTypes(): iterable
    {
        return [
            TaxonType::class,
        ];
    }
}
