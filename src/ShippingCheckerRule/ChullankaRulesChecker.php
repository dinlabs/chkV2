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

        //dd($subject);
        $order = $subject->getOrder();
        if(null === $order) return false;

        $channel = $order->getChannel();
        if(null === $channel) return false;

        if($subject->getMethod())
        {
            $selectedMethod = $subject->getMethod()->getCode();// méthode sélectionnée pour la commande !
        }

        $method = $configuration['method'];
        
        $storeUnavailable = [];
        $oneIsUnavailable = false;
        foreach($order->getItems() as $item)
        {
            $variant = $item->getVariant();

            if($method == 'store')
            {
                // test stock mag
                foreach($variant->getStocks() as $vStock)
                {
                    //init
                    if(!isset($storeUnavailable[ $vStock->getStore()->getId() ]))
                    {
                        $storeUnavailable[ $vStock->getStore()->getId() ] = false;
                    }
                    
                    if($vStock->getOnHand() <= 0)
                    {
                        $storeUnavailable[ $vStock->getStore()->getId() ] = true;
                        $oneIsUnavailable = true;
                    }
                }
            }
            else 
            {
                //test $variant->getOnHand() pour désactiver les méthodes de livraison si pas tous les produits dispo ?
                if($variant->getOnHand() <= 0) $oneIsUnavailable = true;
            }

            
            // test override
            $product = $variant->getProduct();
            $oversize = $product->getAttributeByCodeAndLocale('oversize');
            if(!is_null($oversize) && ($oversize == true))
            {
                $oneIsOversize = true;
            }
        }

        if($method == 'store')
        {
            // si au moins un magasin est à "false" pour les indispo, on affiche la méthode
            for($s=1; $s<count($storeUnavailable); $s++)
            {
                if($storeUnavailable[ $s ] == false) return true;
            }
        }

        if($oneIsUnavailable) return false;

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