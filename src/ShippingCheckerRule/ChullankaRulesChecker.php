<?php

namespace App\ShippingCheckerRule;

use Sylius\Component\Core\Model\ShipmentInterface;
use Sylius\Component\Shipping\Checker\Rule\RuleCheckerInterface;
use Sylius\Component\Shipping\Model\ShippingSubjectInterface;

final class ChullankaRulesChecker implements RuleCheckerInterface
{
    public const TYPE = 'chullanka_rules';

    public function isEligible(ShippingSubjectInterface $subject, array $configuration): bool
    {
        if(!$subject instanceof ShipmentInterface) return false;

        $order = $subject->getOrder();
        if(null === $order) return false;

        $channel = $order->getChannel();
        if(null === $channel) return false;

        //$method = $subject->getMethod()->getCode();//genre "home_standart"

        foreach($order->getItems() as $item)
        {
            $variant = $item->getVariant();

            //test $variant->getOnHand() pour désactiver les méthodes de livraison si pas tous les produits dispo ?

            // test stock mag
            // todo: boucler sur les "store" pour voir ceux où tous les produits sont dispo
            // peut-être créer une méthode pour chaque store (pour les grouper dans la section retrait-magasin) et désactiver celles qui n'ont pas tous les produits ? 
            
            // test override
            $product = $variant->getProduct();
            $oversize = $product->getAttributeByCodeAndLocale('oversize');
            if(!is_null($oversize) && ($oversize == true))
            {
                $oneIsOversize = true;
            }
        }

        if($shipAddress = $order->getShippingAddress())
        {
            $countryCode = $shipAddress->getCountryCode();
        }

        /* $amount = $configuration[$channel->getCode()]['amount'] ?? null;
        if (null === $amount) {
            return false;
        } */

        return true;
    }
}