<?php

namespace App\Menu;

use Sylius\Bundle\UiBundle\Menu\Event\MenuBuilderEvent;

final class AccountMenuListener
{
    public function addAccountMenuItems(MenuBuilderEvent $event): void
    {
        $menu = $event->getMenu();
        $children = $menu->getChildren();;
        $factory = $event->getFactory();

        // RMA
        $children[] = $factory->createItem('rma', ['route' => 'rma_request_list'])
                    ->setLabel('Retours produit')
                    ->setLabelAttribute('icon', 'exchange')
        ;

        // Logout
        $children[] = $factory->createItem('rma', ['route' => 'sylius_shop_logout'])
                    ->setLabel('sylius.ui.logout')
                    ->setLabelAttribute('icon', 'logout')
        ;

        $menu->setChildren($children);
    }
}