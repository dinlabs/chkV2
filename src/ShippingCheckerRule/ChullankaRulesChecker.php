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