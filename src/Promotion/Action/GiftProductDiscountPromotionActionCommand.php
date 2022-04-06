<?php

namespace App\Promotion\Action;

use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Promotion\Action\UnitDiscountPromotionActionCommand;
use Sylius\Component\Promotion\Model\PromotionInterface;
use Sylius\Component\Promotion\Model\PromotionSubjectInterface;
use Sylius\Component\Resource\Exception\UnexpectedTypeException;

class GiftProductDiscountPromotionActionCommand extends UnitDiscountPromotionActionCommand
{
    const TYPE = 'gift_product_discount';

    public function execute(PromotionSubjectInterface $subject, array $configuration, PromotionInterface $promotion): bool
    {
        if (!$subject instanceof OrderInterface) {
            throw new UnexpectedTypeException($subject, OrderInterface::class);
        }

        $channel = $subject->getChannel();
        /*$channelCode = $channel->getCode();
        if (!isset($configuration[$channelCode])) {
            return false;
        }
        $giftProductCode = $configuration[$channelCode]['product_code'];*/
        $giftProductCode = $configuration['product_code'];

        $items = $subject->getItems();
        foreach($items as $item) 
        {
            if($item->getProduct()->getCode() == $giftProductCode)
            {
                $amount = $item->getUnitPrice();
                foreach ($item->getUnits() as $unit) 
                {
                    $this->addAdjustmentToUnit(
                        $unit,
                        min($unit->getTotal(), $amount),
                        $promotion
                    );
                }
            }
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigurationFormType()
    {
        return GiftProductDiscountPromotionActionCommand::class;
    }
}