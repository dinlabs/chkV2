<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

class EventSubscriber implements EventSubscriberInterface
{
    public function onSyliusProductPreCreUpdate(GenericEvent $event)
    {
        $subject = $event->getSubject();
        $chulltest = $subject->getChulltest();
        if(!$chulltest || (empty($chulltest->getDate()) || empty($chulltest->getDescription())))
        {
            $subject->setChulltest(null);
        }
    }

    public static function getSubscribedEvents()
    {
        return [
            'sylius.product.pre_create' => 'onSyliusProductPreCreUpdate',
            'sylius.product.pre_update' => 'onSyliusProductPreCreUpdate',
        ];
    }
}
