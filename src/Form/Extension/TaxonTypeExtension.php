<?php

declare(strict_types=1);

namespace App\Form\Extension;

use App\Form\Type\LinkType;
use Sylius\Bundle\ProductBundle\Form\Type\ProductAutocompleteChoiceType;
use Sylius\Bundle\TaxonomyBundle\Form\Type\TaxonAutocompleteChoiceType;
use Sylius\Bundle\TaxonomyBundle\Form\Type\TaxonType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class TaxonTypeExtension extends AbstractTypeExtension
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('univers', CheckboxType::class, [
            'label' => 'Page Univers'
        ]);

        $builder->add('redirection', TaxonAutocompleteChoiceType::class, [
            'label' => 'Redirection vers une autre catégorie ?',
            'required' => false
        ]);

        /*$builder->add('sub_links', CollectionType::class, [
            'entry_type' => LinkType::class,
            'allow_add' => true,
            'allow_delete' => true,
            'by_reference' => false,
            'label' => 'Liste de liens sous le titre',
            'block_name' => 'entry'
        ]);*/

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
