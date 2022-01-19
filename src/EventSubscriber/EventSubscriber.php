<?php

namespace App\EventSubscriber;

use App\Service\GinkoiaHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

class EventSubscriber implements EventSubscriberInterface
{
    private $entityManager;
    private $ginkoiaHelper;

    public function __construct(EntityManagerInterface $entityManager, GinkoiaHelper $ginkoiaHelper)
    {
        $this->entityManager = $entityManager;
        $this->ginkoiaHelper = $ginkoiaHelper;
    }

    public static function getSubscribedEvents()
    {
        //$eventName = $configuration->getEvent() ?: CartActions::ADD;
        //$metadata = $configuration->getMetadata();
        //error_log(sprintf('%s.%s.pre_%s', $metadata->getApplicationName(), $metadata->getName(), $eventName));


        return [
            'sylius.product.pre_create' => 'onSyliusProductPreCreUpdate',
            'sylius.product.pre_update' => 'onSyliusProductPreCreUpdate',
            'sylius.order.post_select_shipping' => 'onSyliusOrderPostSelectShipping',
            'sylius.order_item.pre_add' => 'onSyliusOrderItemPreAdd',
            'sylius.order.pre_add' => 'onSyliusOrderPreAdd',
            'sylius.order.post_complete' => 'onSyliusOrderPostComplete',
        ];
    }

    public function onSyliusProductPreCreUpdate(GenericEvent $event)
    {
        $subject = $event->getSubject();
        $chulltest = $subject->getChulltest();
        if(!$chulltest || (empty($chulltest->getDate()) || empty($chulltest->getDescription())))
        {
            $subject->setChulltest(null);
        }
    }

    public function onSyliusOrderPostSelectShipping(GenericEvent $event)
    {
        $order = $event->getSubject();
        if($shipment = $order->getShipments())
        {
            $further = null;
            $shipping_method = $shipment->first()->getMethod()->getCode();
            if(($shipping_method == 'store') && isset($_POST['ship_in_store']) && !empty($_POST['ship_in_store']))
            {
                $further = ['store' => (int)$_POST['ship_in_store']];
            }
            $order->setFurther($further);
            $this->entityManager->flush();
        }
    }

    public function onSyliusOrderItemPreAdd(GenericEvent $event)
    {
        error_log("onSyliusOrderItemPreAdd");
        $subject = $event->getSubject();//OrderItem::class
        //dd($subject);
        //$subject->getOrder() ==> null :-( !
    }

    public function onSyliusOrderPreAdd(GenericEvent $event)
    {
        error_log("onSyliusOrderPreAdd");
        $order = $event->getSubject();
        //dd($subject);
    }

    public function onSyliusOrderPostComplete(GenericEvent $event)
    {
        //error_log("onSyliusOrderPostCreate");
        $order = $event->getSubject();
        error_log($this->ginkoiaHelper->export($order));
    }
}
