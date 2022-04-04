<?php

declare(strict_types=1);

namespace App\Entity\Product;

use App\Entity\Chullanka\Stock;
use App\Entity\Chullanka\Store;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Sylius\Component\Core\Model\ProductVariant as BaseProductVariant;
use Sylius\Component\Core\Model\ProductVariantInterface as BaseProductVariantInterface;
use Sylius\Component\Product\Model\ProductInterface;
use Sylius\Component\Product\Model\ProductVariantTranslationInterface;

/**
 * @ORM\Entity
 * @ORM\Table(name="sylius_product_variant")
 */
class ProductVariant extends BaseProductVariant implements BaseProductVariantInterface
{
    /**
     * @ORM\OneToMany(targetEntity=Stock::class, mappedBy="variant", orphanRemoval=true)
     */
    private $stocks;

    public function __construct()
    {
        parent::__construct();
        $this->stocks = new ArrayCollection();
    }

    protected function createTranslation(): ProductVariantTranslationInterface
    {
        return new ProductVariantTranslation();
    }

    public function getProduct(): ?ProductInterface
    {
        $product = parent::getProduct();
        $product->addVariant($this);
        return $product;
    }

    /**
     * @return Collection|Stock[]
     */
    public function getStocks(): Collection
    {
        return $this->stocks;
    }

    public function addStock(Stock $stock): self
    {
        if (!$this->stocks->contains($stock)) {
            $this->stocks[] = $stock;
            $stock->setVariant($this);
        }

        return $this;
    }

    public function removeStock(Stock $stock): self
    {
        if ($this->stocks->removeElement($stock)) {
            // set the owning side to null (unless already changed)
            if ($stock->getVariant() === $this) {
                $stock->setVariant(null);
            }
        }

        return $this;
    }

    public function getStockByStore(Store $store): ?Stock
    {
        if($store->isWarehouse()) return false;
        foreach($this->stocks as $stock)
        {
            if($stock->getStore() === $store)
            {
                return $stock;
            }
        }
        return false;
    }

    /**
     * Renvoi le nombre max dispo en fonction des stocks
     */
    public function getMaxQty()
    {
        $maxQty = (int)$this->getOnHand();
        foreach($this->getStocks() as $stock)
        {
            $onHand = $stock->getStore()->isWarehouse() ? $this->onHand : (int)$stock->getOnHand();
            if($onHand > $maxQty) $maxQty = $onHand;
        }
        return $maxQty;
    }
}
