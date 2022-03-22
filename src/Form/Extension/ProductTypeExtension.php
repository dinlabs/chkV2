<?php

declare(strict_types=1);

namespace App\Form\Extension;

use App\Entity\Chullanka\Brand;
use App\Form\Type\ChulltestType;
use App\Form\Type\BrandAutocompleteChoiceType;
use App\Form\Type\ComplementaryProductType;
use App\Form\Type\FaqType;
use App\Repository\Chullanka\BrandRepository;
use Doctrine\ORM\EntityRepository;
use Sylius\Bundle\ProductBundle\Form\Type\ProductType;
use Sylius\Bundle\ResourceBundle\Form\DataTransformer\ResourceToIdentifierTransformer;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\ReversedTransformer;

class ProductTypeExtension extends AbstractTypeExtension
{
    private $brandRepository;

    public function __construct(BrandRepository $brandRepository)
    {
        $this->brandRepository = $brandRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        //cf. https://github.com/Sylius/Sylius/issues/10078#issuecomment-574567613
        /*$builder->add('brand', BrandAutocompleteChoiceType::class, [
            'label' => 'app.ui.brand',
            'resource' => 'app.brand',
            'choice_name' => 'name',
            'choice_value' => 'id',
            'placeholder' => 'Choisir une marque',
            'required' => false,
        ]);
        $builder->get('brand')->addModelTransformer(
            new ReversedTransformer(
                new ResourceToIdentifierTransformer($this->brandRepository, 'id')
            )
         )->addModelTransformer(
             new ResourceToIdentifierTransformer($this->brandRepository, 'id')
         );*/

        $builder->add('brand', EntityType::class, [
            'class' => Brand::class,
            'label' => 'app.ui.brand',
            'placeholder' => 'Choisir une marque',
            'query_builder' => function (EntityRepository $repository) {
                return $repository->createQueryBuilder('b')->orderBy('b.name', 'ASC');
            },
            'required' => false,
        ]);

        $builder->add('new_from', DateType::class, [
            'widget' => 'single_text',
            'label' => 'Produit "Nouveau" à partir du',
            'required' => false
            ])
            ->add('new_to', DateType::class, [
                'widget' => 'single_text',
                'label' => 'Produit "Nouveau" jusqu\'au',
                'required' => false
            ])
        ;

        $object = $builder->getData();
        $builder->add('chulltest', ChulltestType::class, [
            'object' => $object,
            'label' => false
        ]);

        $builder->add('complementaryProduct', ComplementaryProductType::class, [
            'object' => $object,
            'label' => false
        ]);

        $builder->add('faqs', CollectionType::class, [
            'entry_type' => FaqType::class,
            'allow_add' => true,
            'allow_delete' => true,
            'by_reference' => false,
            'label' => 'FAQ',
            'block_name' => 'entry',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public static function getExtendedTypes(): iterable
    {
        return [
            ProductType::class,
        ];
    }
}
