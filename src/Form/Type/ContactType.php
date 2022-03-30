<?php

declare(strict_types=1);

namespace App\Form\Type;

use App\Entity\Chullanka\Store;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;

final class ContactType extends AbstractType
{

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $storeChoices = [];
        $stores = $this->entityManager->getRepository(Store::class)->findAll();
        foreach($stores as $store)
        {
            $storeChoices[ $store->getCode() ] = $store->getName();
        }

        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Champ obligatoire',
                    ]),
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'sylius.ui.email',
                'constraints' => [
                    new NotBlank([
                        'message' => 'sylius.contact.email.not_blank',
                    ]),
                    new Email([
                        'message' => 'sylius.contact.email.invalid',
                    ]),
                ],
            ])
            ->add('phone', TextType::class, [
                'label' => 'Téléphone',
                'required' => false,
            ])
            ->add('subject', ChoiceType::class, [
                'label' => 'Sujet',
                'choices' => array_flip([
                    null        => 'Choisissez votre sujet',
                    'produits'  => 'Nos produits (caractéristiques, tailles, disponibilités…)',
                    'sav'  	    => 'Service après-vente',
                    'magasins'  => 'Les magasins Chullanka',
                    'site'      => 'Le site Internet chullanka.com',
                    'sponsor'   => 'Partenariat / Sponsoring',
                    'discount'  => 'Code promo / Remises club / devis',
                    'autre'     => 'Autres'
                ]),
                'constraints' => [
                    new NotBlank([
                        'message' => 'Champ obligatoire',
                    ]),
                ],
            ])
            ->add('producturl', TextType::class, [
                'label' => 'Lien de l\'article',
                'required' => false,
            ])
            ->add('orderplace', ChoiceType::class, [
                'label' => 'Où avez vous acheté votre produit ?',
                'choices' => array_flip(array_merge(
                    [null => 'Choisissez le lieu'], $storeChoices, ['chullanka.com' => 'Site Internet chullanka.com']))
            ])
            ->add('number', TextType::class, [
                'label' => 'N° de commande ou n° de ticket',
                'required' => false,
            ])
            ->add('productname', TextType::class, [
                'label' => 'Nom de votre produit',
                'required' => false,
            ])
            ->add('store', ChoiceType::class, [
                'label' => 'Votre magasin',
                'choices' => array_flip(array_merge([
                    null        => 'Choisissez le magasin'
                ], $storeChoices))
            ])
            ->add('subsubject', ChoiceType::class, [
                'label' => 'Sous sujet',
                'choices' => array_flip([
                    null        => 'Choisissez',
                    'code'      => 'Code promo',
                    'partenaire'=> 'Remise partenaire',
                    'devis'     => 'Devis'
                ]),
            ])
            ->add('codepromo', TextType::class, [
                'label' => 'Code promo concerné',
                'required' => false,
            ])
            ->add('storebis', ChoiceType::class, [
                'label' => 'Magasin où vous avez votre remise',
                'choices' => array_flip(array_merge(
                    [null => 'Choisissez le lieu'], $storeChoices, ['chullanka.com' => 'Site Internet chullanka.com'])
                )
            ])
            ->add('partenaire', TextType::class, [
                'label' => 'Votre club partenaire / entreprise partenaire / métiers partenaire',
                'required' => false,
            ])
            ->add('storeter', ChoiceType::class, [
                'label' => 'Devis avec livraison ou retrait en magasin',
                'choices' => array_flip(array_merge(
                    [null => 'Choisissez le lieu'], $storeChoices, ['chullanka.com' => 'Site Internet chullanka.com'])
                )
            ])
            ->add('message', TextareaType::class, [
                'label' => 'Commentaire',
                'constraints' => [
                    new NotBlank([
                        'message' => 'sylius.contact.message.not_blank',
                    ]),
                ],
            ])
            ->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($options): void {
                $email = $options['email'];
                if (null === $email) {
                    return;
                }

                $data = $event->getData();
                $data['email'] = $email;

                $event->setData($data);
            })
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefaults([
                'email' => null,
            ])
        ;
    }

    public function getBlockPrefix(): string
    {
        return 'sylius_contact';
    }
}
