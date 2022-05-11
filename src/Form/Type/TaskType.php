<?php

namespace App\Form\Type;

use App\Entity\Chullanka\Task;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TaskType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $choices = [
            'Choisir une tâche' => '',
            'Relancer l\'indexation Elasticsearch' => 'fos:elastica:populate'
        ];

        $builder
            ->add('command', ChoiceType::class, [
                'label' => 'Commande à exécuter',
                'choices' => $choices
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Task::class,
        ]);
    }
}
