<?php

declare(strict_types=1);

namespace App\Entity\Order;

use App\Entity\Chullanka\Rma;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Setono\SyliusTrustpilotPlugin\Model\OrderTrustpilotAwareInterface;
use Setono\SyliusTrustpilotPlugin\Model\OrderTrait as TrustpilotOrderTrait;
use Sylius\Component\Core\Model\AdjustmentInterface;
use Sylius\Component\Core\Model\Order as BaseOrder;
use Sylius\InvoicingPlugin\Entity\Invoice;

/**
 * @ORM\Entity
 * @ORM\Table(name="sylius_order")
 */
class Order extends BaseOrder implements OrderTrustpilotAwareInterface
{
    use TrustpilotOrderTrait;
    
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
            $s = 1;
            foreach($variant->getStocks() as $stock)
            {
                $inShop[ $variant->getId() ][ $stock->getStore()->getId() ] = (bool)$stock->getOnHand();
                $s++;
            }

            // todo: Comptoir Pro // hack!
            $inShop[ $variant->getId() ][ $s ] = (bool)$variant->getOnHand();
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
     * Test si quantités dépassées
     */
    public function overQuantities()
    {
        $maxQtyByProducts = [];
        $noShip = $noShop = false;
		$inShop = [];
        foreach($this->getItems() as $item)
        {
            $askQty = $item->getQuantity();
            $variant = $item->getVariant();
            $product = $variant->getProduct();

            // pour les packs
            if($product->getIsPack() && ($further = $item->getFurther()) && isset($further['pack']) && count($further['pack']))
            {
                $allVariants = [];
                foreach($product->getPackElements() as $elmt)
                {
                    foreach($elmt->getProducts() as $_prod)
                    {
                        $_variants = $_prod->getVariants();
                        foreach($_variants as $_variant)
                        {
                            $allVariants[ $_variant->getId() ] = $_variant;
                        }
                    }
                }

                $_max = [];
                foreach($further['pack'] as $vid => $price)
                {
                    $_variant = $allVariants[$vid];
                    // test si quantité dispo
                    if($askQty > $_variant->getMaxQty()) 
                        $_max[$vid] = $_variant->getMaxQty();
                }
                if(count($_max) > 0)
                {
                    $maxQtyByProducts[ $item->getId() ] = min($_max);
                }
            }
            else
            {
                // test si quantité dispo
                if($askQty > $variant->getMaxQty()) 
                    $maxQtyByProducts[ $item->getId() ] = $variant->getMaxQty();
            }

        }

        // si des produits ne sont pas dispo en quantite demandee...
        if(count($maxQtyByProducts) > 0)
        {
            error_log(print_r($maxQtyByProducts,true));
            return true;
        }

        return false;
    }

    public function getPaymode()
    {
        $paymode = 'getPaymode';
        if($this->further && isset($this->further['upstreampay_return']) && !empty($this->further['upstreampay_return']))
        {
            error_log("on test");
            $payModeDetails = [];
            foreach($this->further['upstreampay_return'] as $return)
            {
                $codeName = '';
                if(is_array($return))
                {
                    error_log("Tableau");
                    switch($return['method'])
                    {
                        case 'creditcard': $codeName = 'Carte Bancaire'; break;
                        case 'paypal': $codeName = 'PayPal'; break;
                        case 'wallet': if($return['partner'] == 'paypal') $codeName = 'PayPal'; break;
                        case 'cb3x': $codeName = 'en 3 fois'; break;
                        case 'giftcard': $codeName = ($return['partner'] == 'illicado') ? 'en 3 fois' : 'Carte Cadeau'; break;
                    }
                    if(!empty($codeName)) 
                    {
                        $payModeDetails[] = $codeName;
                    }
                    error_log("codename : $codeName");
                }
                else
                {
                    error_log("Obejct");
                    switch($return->method)
                    {
                        case 'creditcard': $codeName = 'Carte Bancaire'; break;
                        case 'paypal': $codeName = 'PayPal'; break;
                        case 'wallet': if($return->partner == 'paypal') $codeName = 'PayPal'; break;
                        case 'cb3x': $codeName = 'en 3 fois'; break;
                        case 'giftcard':
                            $codeName = ($return->partner == 'illicado') ? 'en 3 fois' : 'Carte Cadeau';
                            break;
                    }
                    if(!empty($codeName)) 
                    {
                        $payModeDetails[] = $codeName;
                    }
                }
            }
            if(count($payModeDetails) > 1)
            {
                $codeName = 'Web règlement Mixte';
            }
            if(!empty($codeName)) $paymode = $codeName;
        }
        return $paymode;
    }
}
