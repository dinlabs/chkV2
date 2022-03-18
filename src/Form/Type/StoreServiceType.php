<?php

namespace App\Form\Type;

use App\Entity\Chullanka\Store;
use App\Entity\Chullanka\StoreService;
use BitBag\SyliusCmsPlugin\Form\Type\WysiwygType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class StoreServiceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('enabled', CheckboxType::class, [
                'label' => 'Activé ?'
            ])
            ->add('show_home', CheckboxType::class, [
                'label' => 'Afficher en page d\'accueil ?'
            ])
            ->add('title', TextType::class, [
                'label' => 'Titre du service'
            ])
            ->add('thumbnail', TextType::class, [
                'label' => 'Visuel d\'illustration',
                'required' => false,
                'disabled' => true
            ])
            ->add('thumbnail_file', FileType::class, [
                'label' => 'Télécharger un nouveau visuel (si besoin)',
                'required' => false,
            ])
            ->add('content', WysiwygType::class, [
                'label' => 'Contenu'
            ])
            ->add('stores', EntityType::class, [
                'class' => Store::class,
                'label' => 'app.ui.store',
                'placeholder' => 'Choisir un magasin',
                'multiple' => true,
                'required' => false
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => StoreService::class,
        ]);
    }
}
