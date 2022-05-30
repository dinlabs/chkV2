<?php

namespace App\EventSubscriber;

use App\Entity\Chullanka\Parameter;
use App\Entity\Shipping\ShippingMethod;
use Doctrine\ORM\EntityManagerInterface;
use Sylius\Bundle\ResourceBundle\Event\ResourceControllerEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Twig\Environment;

class TwigEventSubscriber implements EventSubscriberInterface
{
    private $twig;
    private $entityManager;

    public function __construct(Environment $twig, EntityManagerInterface $entityManager)
    {
        $this->twig = $twig;
        $this->entityManager = $entityManager;
    }
    private function chkParameter($slug)
    {
        return $this->entityManager->getRepository(Parameter::class)->getValue($slug);
    }
    

    public static function getSubscribedEvents()
    {
        return [
            'kernel.controller' => 'onKernelController',
            'sylius.product.show' => 'onShowProduct',
        ];
    }

    public function onKernelController(ControllerEvent $event)
    {
        // variables dispo dans tous les templates

        $customerId = $this->chkParameter('t2s-customer-id');//'JINRXA62YWCZ2V';
        $this->twig->addGlobal('t2scID', $customerId);
    }

    public function onShowProduct(ResourceControllerEvent $event)
    {
        // variables dispo dans le template Product
        // shipping method
        $shipMethod = $this->entityManager->getRepository(ShippingMethod::class)->findOneBy(['code' => 'home_standart']);
        $conf = $shipMethod->getConfiguration();
        $this->twig->addGlobal('freeAbove', $conf['free_above']);
        $this->twig->addGlobal('defaultPrice', $conf['price']);
    }
}
