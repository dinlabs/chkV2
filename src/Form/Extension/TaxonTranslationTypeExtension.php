<?php

declare(strict_types=1);

namespace App\Form\Extension;

use BitBag\SyliusCmsPlugin\Form\Type\WysiwygType;
use Sylius\Bundle\TaxonomyBundle\Form\Type\TaxonTranslationType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;

class TaxonTranslationTypeExtension extends AbstractTypeExtension
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('content', WysiwygType::class, [
            'label' => 'Contenu'
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public static function getExtendedTypes(): iterable
    {
        return [
            TaxonTranslationType::class,
        ];
    }
}
