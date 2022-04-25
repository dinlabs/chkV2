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
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RmaAdminType extends AbstractType
{
    private $customer = null;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        //$this->customer = $options['data']->getCustomer();
        $this->customer = $builder->getData()->getCustomer();

        $builder
            ->add('number', TextType::class, [
                'label' => 'Référence'
            ])
            ->add('phone_number', TextType::class, [
                'label' => 'Téléphone du client'
            ])
            ->add('customer_email', EmailType::class, [
                'label' => 'Email du client'
            ])
            ->add('address', EntityType::class, [
                'label' => 'Adresse du client',
                'class' => Address::class,
                'query_builder' => function (EntityRepository $er) {
                    return $er	->createQueryBuilder('a')
                                ->where('a.customer = (:customer)')
                                ->setParameter('customer', $this->customer)
                    ;
                },
            ])
            ->add('state', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => array_flip([
                    'new' => 'Nouvelle demande',
                    'product_received' => 'Produits reçus',
                    'expertise_product' => 'Produits en expertise',
                    'product_return_accepted' => 'Retour produit accepté',
                    'product_return_refused' => 'Retour produit refusé',
                    'complete' => 'Terminé',
                    'expired' => 'Expiré'
                ])
            ])
            ->add('reception_at', DateType::class, [
                'required' => false,
                'label' => 'Date de réception du produit',
                'widget' => 'single_text'
            ])
            ->add('return_at', DateType::class, [
                'required' => false,
                'label' => 'Date du retour produit',
                'widget' => 'single_text'
            ])
            ->add('public_comment', TextareaType::class, [
                'required' => false,
                'label' => 'Commentaire public'
            ])
            ->add('private_comment', TextareaType::class, [
                'required' => false,
                'label' => 'Commentaire privé'
            ])
            /*->add('rmaProducts', CollectionType::class, [
                'entry_type' => RmaProductType::class,
            ])*/
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Rma::class
        ]);
    }
}
