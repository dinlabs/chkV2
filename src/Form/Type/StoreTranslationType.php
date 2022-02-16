<?php

namespace App\Form\Type;

use App\Entity\Chullanka\StoreTranslation;
use BitBag\SyliusCmsPlugin\Form\Type\WysiwygType;
use Sylius\Bundle\ResourceBundle\Form\Type\AbstractResourceType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class StoreTranslationType extends AbstractResourceType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('introduction', TextareaType::class, [
                'label' => 'app.store.introduction',
                'required' => false
            ])
            ->add('warning', TextareaType::class, [
                'label' => 'app.store.warning',
                'required' => false
            ])
            ->add('opening_hours', TextareaType::class, [
                'label' => 'app.store.opening_hours',
                'required' => false
            ])
            ->add('description', WysiwygType::class, [
                'label' => 'app.store.description',
                'required' => false
            ])
            ->add('director_note', TextareaType::class, [
                'label' => 'app.store.director_note',
                'required' => false
            ])
            ->add('advertising', WysiwygType::class, [
                'label' => 'Encart promo',
                'required' => false
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => StoreTranslation::class,
        ]);
    }
}
