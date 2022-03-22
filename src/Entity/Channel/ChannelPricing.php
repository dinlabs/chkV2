<?php

declare(strict_types=1);

namespace App\Entity\Channel;

use Doctrine\ORM\Mapping as ORM;
use Sylius\Component\Core\Model\ChannelPricing as BaseChannelPricing;

/**
 * @ORM\Entity
 * @ORM\Table(name="sylius_channel_pricing")
 */
class ChannelPricing extends BaseChannelPricing
{
    /** @ORM\Column(name="discount_price", type="integer", nullable=true) */
    protected $discountPrice;
    
    /** @ORM\Column(name="discount_from", type="date", nullable=true) */
    protected $discountFrom;
    
    /** @ORM\Column(name="discount_to", type="date", nullable=true) */
    protected $discountTo;

    public function getDiscountPrice(): ?int
    {
        return $this->discountPrice;
    }
    
    public function setDiscountPrice(?int $discountPrice): void
    {
        $this->discountPrice = $discountPrice;
    }
    
    public function getDiscountFrom(): ?\DateTime
    {
        return $this->discountFrom;
    }
    
    public function setDiscountFrom(?\DateTime $discountFrom): void
    {
        $this->discountFrom = $discountFrom;
    }
    
    public function getDiscountTo(): ?\DateTime
    {
        return $this->discountTo;
    }
    
    public function setDiscountTo(?\DateTime $discountTo): void
    {
        $this->discountTo = $discountTo;
    }

    public function getPercentage()
    {
        $price = $this->getPrice();
        $orig = $this->getOriginalPrice();
        if(!empty($price) && !empty($orig) && ($price != $orig)) 
        {
            $percent = 100 - round(($price / $orig) * 100);
            return $percent;
        }
        return '';
    }
}
