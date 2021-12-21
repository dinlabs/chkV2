<?php

namespace App\Form\Type;

use App\Entity\Chullanka\Chulli;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ChulliType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstname', TextType::class, [
                'label' => 'app.chulli.firstname'
            ])
            ->add('lastname', TextType::class, [
                'label' => 'app.chulli.lastname',
                'required' => false
            ])
            ->add('expertise', TextType::class, [
                'label' => 'app.chulli.expertise',
                'required' => false
            ])
            /*->add('avatar', TextType::class, [
                'label' => 'app.chulli.avatar',
                'required' => false,
            ])*/
            /*->add('avatar', FileType::class, [
                'label' => 'app.chulli.avatar',
                'required' => false,
                'data_class' => null
            ])*/
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Chulli::class,
        ]);
    }
}
