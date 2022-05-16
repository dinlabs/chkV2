<?php

declare(strict_types=1);

namespace App\ShippingCalculator;

use Sylius\Component\Shipping\Calculator\CalculatorInterface;
use Sylius\Component\Shipping\Model\ShipmentInterface;

final class ChullankaCalculator implements CalculatorInterface
{
    public function calculate(ShipmentInterface $subject, array $configuration): int
    {
        $method = $subject->getMethod()->getCode();//genre "home_standart"
        
        /*$configuration = [
            'default' => [
                'amount' => 500
            ],
            'price' => 600,
            'sup_outside_france' => 500,
            'free_above' => 12000
        ];*/
        $price = $configuration['price'];
        $supOutsideFrance = $configuration['sup_outside_france'];
        $freeAbove = $configuration['free_above'];
        $canBeFree = true;
        
        $order = $subject->getOrder();
        if($shipAddress = $order->getShippingAddress())
        {
            $countryCode = $shipAddress->getCountryCode();
            if($countryCode == 'FR')
            {
                $postcode = $shipAddress->getPostcode();
                $department = substr($postcode, 0, 2);
                if(in_array($department, ['97','98']))
                {
                    $price += $supOutsideFrance;
                    $canBeFree = false;
                }
            }
            else
            {
                $price += $supOutsideFrance;
                $canBeFree = false;
            }
        }

        if($canBeFree && $freeAbove)
        {
            $totalCart = $order->getItemsTotal();
            /*$totalCart = 0;
            $oneIsOversize = false;
            foreach($order->getItems() as $item)
            {
                $totalCart += $item->getTotal();
                
                // test override
                $variant = $item->getVariant();
                $product = $variant->getProduct();
                $oversize = $product->getAttributeByCodeAndLocale('oversize');
                if(!is_null($oversize) && ($oversize == true))
                {
                    $oneIsOversize = true;
                }
            }*/
            
            if($totalCart >= $freeAbove) $price = 0;
        }
        
        return (int) ($price);
    }

    public function getType(): string
    {
        return 'chullanka';
    }
}