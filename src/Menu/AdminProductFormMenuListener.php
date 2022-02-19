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
        $children = $menu->getChildren();;
        $factory = $event->getFactory();

        // Chulltest
        $children[] = $factory  ->createItem('chull_test')
        ->setLabel('Chull Test')
        ->setAttribute('template', 'bundles/SyliusAdminBundle/Product/Tab/_chulltest.html.twig')
        ;
        
        // FAQs
        $children[] = $factory  ->createItem('faqs')
                                ->setLabel('FAQ')
                                ->setAttribute('template', 'bundles/SyliusAdminBundle/Product/Tab/_faqs.html.twig')
        ;
        
        // ComplementaryProduct
        $children[] = $factory  ->createItem('complementary_product')
        ->setLabel('Produits complémentaires')
        ->setAttribute('template', 'bundles/SyliusAdminBundle/Product/Tab/_complementary.html.twig')
        ;
        $menu->setChildren($children);
    }
}
