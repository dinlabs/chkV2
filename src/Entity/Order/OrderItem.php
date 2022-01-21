<?php

declare(strict_types=1);

namespace App\Entity\Order;

use Doctrine\ORM\Mapping as ORM;
use Sylius\Component\Core\Model\OrderItem as BaseOrderItem;

/**
 * @ORM\Entity
 * @ORM\Table(name="sylius_order_item")
 */
class OrderItem extends BaseOrderItem
{
    /**
     * @ORM\Column(type="json", nullable=true)
     */
    private $further = [];

    public function getFurther(): ?array
    {
        return $this->further;
    }

    public function setFurther(?array $further): self
    {
        $this->further = $further;

        return $this;
    }

    public function setUnitPrice(int $unitPrice): void
    {
        if(!empty($this->further) && isset($this->further['pack']) && !empty($this->further['pack']))
        {
            $unitPrice = 0;
            foreach($this->further['pack'] as $vid => $vPrice)
            {
                $unitPrice += $vPrice;
            }
        }
        $this->unitPrice = $unitPrice;
        
        $this->recalculateUnitsTotal();
    }
}
