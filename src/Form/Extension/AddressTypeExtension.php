<?php
declare(strict_types=1);

namespace App\Form\Extension;

use Sylius\Bundle\AddressingBundle\Form\Type\AddressType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Constraints\NotBlank;

final class AddressTypeExtension extends AbstractTypeExtension
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('phoneNumber', TextType::class, [
            'required' => true,
            'label' => 'sylius.form.address.phone_number',
            'constraints' => [new NotBlank(['groups' => ['sylius']])]
        ]);

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event): void {
            $form = $event->getForm();
            $form->remove('provinceName');
        });
    }
    
    public static function getExtendedTypes(): iterable
    {
        return [AddressType::class];
    }
}