<?php

declare(strict_types=1);

namespace App\Entity\Product;

use App\Entity\Chullanka\Stock;
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
     * @ORM\OneToOne(targetEntity=Stock::class, mappedBy="variant", cascade={"persist", "remove"})
     */
    private $store;

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

    public function getStore(): ?Stock
    {
        return $this->store;
    }

    public function setStore(?Stock $store): self
    {
        // unset the owning side of the relation if necessary
        if ($store === null && $this->store !== null) {
            $this->store->setVariant(null);
        }

        // set the owning side of the relation if necessary
        if ($store !== null && $store->getVariant() !== $this) {
            $store->setVariant($this);
        }

        $this->store = $store;

        return $this;
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
}
