<?php

namespace App\Form\Type\Action;

use App\Form\DataTransformer\BrandsToCodesTransformer;
use App\Form\Type\BrandAutocompleteChoiceType;
use Sylius\Bundle\ProductBundle\Form\Type\ProductAutocompleteChoiceType;
use Sylius\Bundle\ResourceBundle\Form\DataTransformer\ResourceToIdentifierTransformer;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\ReversedTransformer;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;

final class HasBrandConfigurationType extends AbstractType
{
    private DataTransformerInterface $brandsToCodesTransformer;

    public function __construct(DataTransformerInterface $brandsToCodesTransformer)
    {
        $this->brandsToCodesTransformer = $brandsToCodesTransformer;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('brands_codes', BrandAutocompleteChoiceType::class, [
                'label' => 'Quelles marques ?',
                'multiple' => true,
            ])
        ;

        $builder->get('brands_codes')->addModelTransformer($this->brandsToCodesTransformer);
    }

    public function getBlockPrefix(): string
    {
        return 'app_promotion_action_has_brand_configuration';
    }
}