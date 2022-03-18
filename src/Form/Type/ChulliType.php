<?php

namespace App\Form\Type;

use App\Entity\Chullanka\Chulli;
use App\Entity\Chullanka\Store;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ChulliType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('enabled', CheckboxType::class, [
                'label' => 'app.chulli.enabled'
            ])
            ->add('firstname', TextType::class, [
                'label' => 'app.chulli.firstname'
            ])
            ->add('lastname', TextType::class, [
                'label' => 'app.chulli.lastname',
                'required' => false
            ])
            ->add('store', EntityType::class, [
                'class' => Store::class,
                'label' => 'app.ui.store',
                'placeholder' => 'Choisir un magasin',
                'required' => false
            ])
            ->add('leader', CheckboxType::class, [
                'label' => 'Responsable du magasin ?'
            ])
            ->add('expertise', TextType::class, [
                'label' => 'app.chulli.expertise',
                'required' => false
            ])
            ->add('avatar', TextType::class, [
                'label' => 'app.chulli.avatar',
                'required' => false,
                'disabled' => true
            ])
            ->add('avatar_file', FileType::class, [
                'label' => 'Télécharger un nouvel avatar (si besoin)',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Chulli::class,
        ]);
    }
}
