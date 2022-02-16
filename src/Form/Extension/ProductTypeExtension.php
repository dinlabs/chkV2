<?php

declare(strict_types=1);

namespace App\Form\Extension;

use App\Entity\Chullanka\Brand;
use App\Form\Type\ChulltestType;
use App\Form\Type\BrandAutocompleteChoiceType;
use App\Repository\Chullanka\BrandRepository;
use Doctrine\ORM\EntityRepository;
use Sylius\Bundle\ProductBundle\Form\Type\ProductType;
use Sylius\Bundle\ResourceBundle\Form\DataTransformer\ResourceToIdentifierTransformer;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractTypeExtension;
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

        $object = $builder->getData();
        $builder->add('chulltest', ChulltestType::class, [
            'object' => $object,
            'label' => false
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
