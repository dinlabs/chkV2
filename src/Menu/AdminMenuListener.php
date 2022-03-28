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

        // récupération du nouveau child pour le mettre en premier
        $children = $menu->getChildren();
        //$chullankaMenu = array_pop($children);
        //$children = array_merge([$chullankaMenu], $children);
        $chullankaMenu = $children['chullanka_options'];
        unset($children['chullanka_options']);
        array_unshift($children, $chullankaMenu);
        $menu->setChildren($children);
    }

    private function addChild(ItemInterface $item): void
    {
        //Icons : https://fontawesome.com/v5/search

        $item
            ->addChild('chullis', [
                'route' => 'app_admin_chulli_index',
            ])
            ->setLabel('chullanka_chullis.menu_item')
            ->setLabelAttribute('icon', 'users');
        
        $item
            ->addChild('brands', [
                'route' => 'app_admin_brand_index',
            ])
            ->setLabel('chullanka_brands.menu_item')
            ->setLabelAttribute('icon', 'building');
        
        $item
            ->addChild('stores', [
                'route' => 'app_admin_store_index',
            ])
            ->setLabel('chullanka_stores.menu_item')
            ->setLabelAttribute('icon', 'map marker alternate');
        
        $item
            ->addChild('store-services', [
                'route' => 'app_admin_store_service_index',
            ])
            ->setLabel('chullanka_store_services.menu_item')
            ->setLabelAttribute('icon', 'cubes');
        
        $item
            ->addChild('sports', [
                'route' => 'app_admin_sport_index',
            ])
            ->setLabel('chullanka_sports.menu_item')
            ->setLabelAttribute('icon', 'futbol');
        
        $item
            ->addChild('recalls', [
                'route' => 'app_admin_recall_index',
            ])
            ->setLabel('chullanka_recalls.menu_item')
            ->setLabelAttribute('icon', 'phone');
        
        $item
            ->addChild('rmas', [
                'route' => 'app_admin_rma_index',
            ])
            ->setLabel('chullanka_rmas.menu_item')
            ->setLabelAttribute('icon', 'exchange');

        $item
            ->addChild('parameters', [
                'route' => 'app_admin_parameter_index',
            ])
            ->setLabel('chullanka_parameters.menu_item')
            ->setLabelAttribute('icon', 'cog');
    }
}