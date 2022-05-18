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
        $product = $event->getProduct();
        $menu = $event->getMenu();
        $children = $menu->getChildren();;
        $factory = $event->getFactory();
        
        // Données importées
        if($product->getImportedData())
        {
            $children['imported_data'] = $factory  ->createItem('imported_data')
                                    ->setLabel('Données importées')
                                    ->setAttribute('template', 'bundles/SyliusAdminBundle/Product/Tab/_imported_data.html.twig')
            ;
        }

        // Pack
        if($product->getIsPack())
        {
            $children['packs'] = $factory  ->createItem('packs')
                                    ->setLabel('Produits du pack')
                                    ->setAttribute('template', 'bundles/SyliusAdminBundle/Product/Tab/_packs.html.twig')
            ;
        }

        // Chulltest
        $children['chull_test'] = $factory  ->createItem('chull_test')
                                ->setLabel('Chull Test')
                                ->setAttribute('template', 'bundles/SyliusAdminBundle/Product/Tab/_chulltest.html.twig')
        ;
        
        // FAQs
        $children['faqs'] = $factory  ->createItem('faqs')
                                ->setLabel('FAQ')
                                ->setAttribute('template', 'bundles/SyliusAdminBundle/Product/Tab/_faqs.html.twig')
        ;
        
        // ComplementaryProduct
        $children['complementary_product'] = $factory  ->createItem('complementary_product')
                                ->setLabel('Produits complémentaires')
                                ->setAttribute('template', 'bundles/SyliusAdminBundle/Product/Tab/_complementary.html.twig')
        ;
        
        // !!! CE TRI PROVOQUE UN PROBLEME POUR L'ONGLET PACK DONT IL MANQUE ALORS remote_url !!!
        // début tri
        //dd($children);
        /* Current order:
        "details"
        "taxonomy"
        "attributes"
        "associations"
        "media"
        "inventory"
        "imported_data"
        "chull_test"
        "faqs"
        "complementary_product"
        */
        /*$newChild = [
            "imported_data" => [],
            "details" => [],
            "packs" => [],
            "taxonomy" => [],
            "attributes" => [],
            "associations" => [],
            "media" => [],
            "inventory" => [],
            "faqs" => [],
            "chull_test" => [],
            "complementary_product" => [],
        ];
        $tmpChildren = array_merge($newChild, $children);
        $children = [];
        foreach($tmpChildren as $key => $child) if($child) $children[ $key ] = $child;
        // fin tri*/

        $menu->setChildren($children);
    }
}
