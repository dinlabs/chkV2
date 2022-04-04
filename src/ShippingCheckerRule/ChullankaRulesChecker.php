<?php

namespace App\ShippingCheckerRule;

use App\Entity\Chullanka\Store;
use Doctrine\Persistence\ManagerRegistry;
use Sylius\Component\Core\Model\ShipmentInterface;
use Sylius\Component\Shipping\Checker\Rule\RuleCheckerInterface;
use Sylius\Component\Shipping\Model\ShippingSubjectInterface;

final class ChullankaRulesChecker implements RuleCheckerInterface
{
    public const TYPE = 'chullanka_rules';

    /** @var ManagerRegistry */
    private $managerRegistry;

    public function __construct(ManagerRegistry $managerRegistry)
    {
        $this->managerRegistry = $managerRegistry;
    }

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
        if($method == 'store')
        {
            $stores = $this->managerRegistry->getRepository(Store::class)->findAll();
        }
        
        $storeUnavailable = [];
        $oneIsUnavailable = false;
        foreach($order->getItems() as $item)
        {
            $variant = $item->getVariant();

            if($method == 'store')
            {
                // test stock mag
                foreach($stores as $store)
                {
                    if($store->isWarehouse())
                    {
                        $onHand = $variant->getOnHand();
                    }
                    else
                    {
                        $stock = $variant->getStockByStore($store);
                        if(!$stock) $onHand = false;
                        else $onHand = $stock->getOnHand();
                    }

                    //init
                    if(!isset($storeUnavailable[ $store->getId() ]))
                    {
                        $storeUnavailable[ $store->getId() ] = false;
                    }

                    if($onHand <= 0)
                    {
                        $storeUnavailable[ $store->getId() ] = true;
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
            for($s=1; $s<=count($storeUnavailable); $s++)
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