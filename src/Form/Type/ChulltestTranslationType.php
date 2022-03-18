<?php

namespace App\Form\Type;

use BitBag\SyliusCmsPlugin\Form\Type\WysiwygType;
use Sylius\Bundle\ResourceBundle\Form\Type\AbstractResourceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ChulltestTranslationType extends AbstractResourceType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('headline', TextType::class, [
                'label' => 'Accroche d\'entête'
            ])
            ->add('description', WysiwygType::class, [
                'label' => 'app.chull_test.description',
                'required' => false
            ])
            ->add('sumup', TextType::class, [
                'label' => 'Phrase pour résumer',
                'required' => false
            ])
            ->add('pros', TextareaType::class, [
                'label' => 'app.chull_test.pros',
                'required' => false
            ])
            ->add('cons', TextareaType::class, [
                'label' => 'app.chull_test.cons',
                'required' => false
            ])
        ;
    }
}
