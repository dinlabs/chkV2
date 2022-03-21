<?php

declare(strict_types=1);

namespace App\Entity\Order;

use App\Entity\Chullanka\Rma;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Sylius\Component\Core\Model\AdjustmentInterface;
use Sylius\Component\Core\Model\Order as BaseOrder;
use Sylius\InvoicingPlugin\Entity\Invoice;

/**
 * @ORM\Entity
 * @ORM\Table(name="sylius_order")
 */
class Order extends BaseOrder
{
    /**
     * @ORM\OneToMany(targetEntity=Invoice::class, mappedBy="order")
     */
    private $invoices;

    /**
     * @ORM\Column(type="json", nullable=true)
     */
    private $further = [];

    /**
     * @ORM\OneToMany(targetEntity=Rma::class, mappedBy="order")
     */
    private $rmas;

    public function __construct()
    {
        parent::__construct();
        $this->invoices = new ArrayCollection();
        $this->rmas = new ArrayCollection();
    }

    /**
     * @return Collection|Invoice[]
     */
    public function getInvoices(): Collection
    {
        return $this->invoices;
    }

    public function getFurther(): ?array
    {
        return $this->further;
    }

    public function setFurther(?array $further): self
    {
        $this->further = $further;

        return $this;
    }

    /**
     * for Twig templates
     */
    public function chullpoints()
    {
        $chullPoints = false;

        $adjustements = $this->getAdjustments( AdjustmentInterface::ORDER_PROMOTION_ADJUSTMENT );
        foreach($adjustements as $adjustement)
        {
            if($adjustement->getOriginCode() == 'chk_chullpoints')
            {
                $chullPoints = $adjustement->getAmount();
                break;
            }
        }

        return $chullPoints;
    }

    /**
     * Test si panier mixte
     */
    public function isPanierMixte()
    {
        $noShip = $noShop = false;
		$inShop = [];
        foreach($this->getItems() as $item)
        {
            $askQty = $item->getQuantity();
            $variant = $item->getVariant();

            if(!$variant->getOnHand() < $askQty) $noShip = true;
            foreach($variant->getStocks() as $stock)
            {
                $inShop[ $variant->getId() ][ $stock->getStore()->getId() ] = (bool)$stock->getOnHand();
            }
        }
        
        // test tous les produits par magasin
		$noShop = true;
		foreach($inShop as $p => $dispo)
		{
			if(!isset($dispoShops)) $dispoShops = $dispo;
			$dispoShops = array_intersect_assoc($dispoShops, $dispo);
		}
        if(isset($dispoShops))
        {
            foreach($dispoShops as $s => $dispo)
            {
                if($dispo) $noShop = false;
            }
        }
		
		// si c'est indispo en livraison et en magasin pour certains produits = panier mixte
		if((count($this->getItems()) > 1) && $noShip && $noShop) 
        {
            return true;
        }

        return false;
    }

    /**
     * @return Collection|Rma[]
     */
    public function getRmas(): Collection
    {
        return $this->rmas;
    }

    public function addRma(Rma $rma): self
    {
        if (!$this->rmas->contains($rma)) {
            $this->rmas[] = $rma;
            $rma->setRmaOrder($this);
        }

        return $this;
    }

    public function removeRma(Rma $rma): self
    {
        if ($this->rmas->removeElement($rma)) {
            // set the owning side to null (unless already changed)
            if ($rma->getRmaOrder() === $this) {
                $rma->setRmaOrder(null);
            }
        }

        return $this;
    }
}
