<?php

declare(strict_types=1);

namespace App\Menu;

use Sylius\Bundle\AdminBundle\Event\ProductMenuBuilderEvent;

final class AdminProductFormMenuListener
{
    /**
     * @param ProductMenuBuilderEvent $event
     */
    public function addItems(ProductMenuBuilderEvent $event): void
    {
        $menu = $event->getMenu();

        $menu
            ->addChild('chull_test')
            ->setLabel('Chull Test')
            ->setAttribute('template', 'bundles/SyliusAdminBundle/Product/Tab/_chulltest.html.twig')
        ;
    }
}
