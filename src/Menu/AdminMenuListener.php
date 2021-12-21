<?php

namespace App\Menu;

use Knp\Menu\ItemInterface;
use Sylius\Bundle\UiBundle\Menu\Event\MenuBuilderEvent;

final class AdminMenuListener
{
    public function addAdminMenuItems(MenuBuilderEvent $event): void
    {
        $menu = $event->getMenu();

        $chullankaRootMenuItem = $menu
            ->addChild('chullanka_options')
            ->setLabel('Chullanka')
        ;
        $chullankaRootMenuItem->actsLikeFirst(true);
        $this->addChild($chullankaRootMenuItem);
    }

    private function addChild(ItemInterface $item): void
    {
        $item
            ->addChild('stores', [
                'route' => 'app_admin_store_index',
            ])
            ->setLabel('chullanka_stores.menu_item')
            ->setLabelAttribute('icon', 'map marker alternate');
        $item
            ->addChild('chullis', [
                'route' => 'app_admin_chulli_index',
            ])
            ->setLabel('chullanka_chullis.menu_item')
            ->setLabelAttribute('icon', 'users');
    }
}