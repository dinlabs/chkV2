<?php

declare(strict_types=1);

namespace App\Promotion;

use Sylius\Component\Order\Factory\AdjustmentFactoryInterface;
use Sylius\Component\Order\Model\OrderInterface;
use Sylius\Component\Core\Model\AdjustmentInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class CheckoutStateMachineCallback
{
    private $session;
    private $adjustmentFactory;

    public function __construct(SessionInterface $session, AdjustmentFactoryInterface $adjustmentFactory)
    {
        $this->session = $session;
        $this->adjustmentFactory = $adjustmentFactory;
    }

    public function onStateChange(OrderInterface $order): void
    {
        // fidelity
        $usedchullpoints = $this->session->get('usedchullpoints');
        if($usedchullpoints && (abs($usedchullpoints) > 0))
        {
            $codeName = 'chk_chullpoints';

            // en principe, la réduction n'existe déjà plus!
            $found = false;
            foreach($order->getAdjustments() as $adjustement)
            {
                if($adjustement->getOriginCode() == $codeName)
                {
                    //$order->removeAdjustment($adjustement);
                    $found = true;
                }
            }

            // si on en a définie une, mais qu'elle n'est plus trouvée...
            if(!$found)
            {
                // ...on va la recréer
                $chullz = 0; //nbr de points
                if($customer = $order->getCustomer())
                {
                    $chullz = $customer->getChullpoints(); //nbr de points sur le site
                }
                error_log("chullz : $chullz");
                $nbrReduc = (int)floor($chullz / 500); // 500 points = 1 bon
                $discountAmount = $nbrReduc * 10; // 1 bon = 10€
                $amount = -100 * (int) $discountAmount;
                $adjustment = $this->adjustmentFactory->createWithData(
                    AdjustmentInterface::ORDER_PROMOTION_ADJUSTMENT,
                    $codeName,
                    $amount
                );
                $adjustment->setOriginCode($codeName);
                $order->addAdjustment($adjustment);
            }
        }
    }
}