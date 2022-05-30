<?php

declare(strict_types=1);

namespace App\Form\Extension;

use App\Entity\Channel\ChannelPricing;
use App\Entity\Chullanka\Brand;
use App\Form\Type\ChulltestType;
//use App\Form\Type\BrandAutocompleteChoiceType;
use App\Form\Type\ComplementaryProductType;
use App\Form\Type\FaqType;
use App\Form\Type\PackElementType;
use App\Repository\Chullanka\BrandRepository;
use Doctrine\ORM\EntityRepository;
use Sylius\Bundle\CoreBundle\Form\Type\ChannelCollectionType;
use Sylius\Bundle\CoreBundle\Form\Type\Product\ChannelPricingType;
use Sylius\Bundle\ProductBundle\Form\Type\ProductOptionChoiceType;
use Sylius\Bundle\ProductBundle\Form\Type\ProductType;
use Sylius\Bundle\ResourceBundle\Form\DataTransformer\ResourceToIdentifierTransformer;
use Sylius\Component\Core\Model\ChannelInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\ReversedTransformer;
use Symfony\Component\HttpFoundation\RequestStack;

class ProductTypeExtension extends AbstractTypeExtension
{
    /** @var RequestStack */
    private $requestStack;

    private $brandRepository;

    public function __construct(RequestStack $requestStack, BrandRepository $brandRepository)
    {
        $this->requestStack = $requestStack;
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

        $builder->add('mounting', ChoiceType::class, [
            'label' => 'Option de montage',
            'choices' => [
                'Non' => null,
                'Oui, pour des Skis' => 1,
                'Oui, pour un Snowboard' => 2,
            ]
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

        //Cherche si l'url contient "pack"
        $pathInfo = $this->requestStack->getCurrentRequest()->getPathInfo();
        if(strpos($pathInfo, 'pack') > -1) 
        {
            $object->setIsPack(true);
        }
        $builder->add('isPack', HiddenType::class);
        $builder->add('packElements', CollectionType::class, [
            'entry_type' => PackElementType::class,
            'allow_add'    => true,
            'allow_delete' => true,
            'by_reference' => false,
            'label' => 'Produits du pack',
            'block_name' => 'entry',
        ]);

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event): void {
            $form = $event->getForm();
            $form->add('options', ProductOptionChoiceType::class, [
                'required' => false,
                'disabled' => false,// surcharge pour autoriser la modif
                'multiple' => true,
                'label' => 'sylius.form.product.options',
            ]);


            //todo: faire en sorte que le prix ne soit pas obligatoire si c'est un pack
            /*
            $product = $event->getData();
            $variants = $product->getVariants();
            $productVariant = $variants->first();
            $chanenlPricings = $productVariant->getChannelPricings();
            if(count($chanenlPricings))
            {

                foreach($chanenlPricings as $chanenlPricing)
                {
                    $chanenlPricing->setPrice(0);
                }
            }
            else
            {
                $chanenlPricing = new ChannelPricing();
                $chanenlPricing->setPrice(0);
                $productVariant->addChannelPricing($chanenlPricing);
            }

            // $event->getForm()->add('channelPricings', ChannelCollectionType::class, [
            //     'entry_type' => ChannelPricingType::class,
            //     'entry_options' => fn(ChannelInterface $channel) => [
            //         'channel' => $channel,
            //         'product_variant' => $productVariant,
            //         'required' => false,
            //     ],
            //     'label' => 'sylius.form.variant.price',
            // ]);
            */

        });
        
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
