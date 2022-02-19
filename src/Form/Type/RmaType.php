<?php

namespace App\Form\Type;

use App\Entity\Addressing\Address;
use App\Entity\Chullanka\Rma;
use Doctrine\ORM\EntityRepository;
use Sylius\Bundle\ResourceBundle\Form\Type\AbstractResourceType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RmaType extends AbstractType
{
    private $customer = null;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        //$this->customer = $options['data']->getCustomer();
        $this->customer = $builder->getData()->getCustomer();

        $builder
            ->add('phone_number')
            ->add('customer_email', EmailType::class)
            ->add('address', EntityType::class, [
                'class' => Address::class,
                'query_builder' => function (EntityRepository $er) {
                    return $er	->createQueryBuilder('a')
                                ->where('a.customer = (:customer)')
                                ->setParameter('customer', $this->customer)
                    ;
                },
            ])
            ->add('rmaProducts', CollectionType::class, [
                'entry_type' => RmaProductType::class,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Rma::class
        ]);
    }
}
