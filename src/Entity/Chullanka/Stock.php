<?php

namespace App\Entity\Chullanka;

use App\Entity\Product\ProductVariant;
use App\Repository\Chullanka\StockRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=StockRepository::class)
 * @ORM\Table(name="nan_chk_stock")
 */
class Stock
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=ProductVariant::class, inversedBy="stocks")
     * @ORM\JoinColumn(nullable=false)
     */
    private $variant;

    /**
     * @ORM\ManyToOne(targetEntity=Store::class, inversedBy="stocks")
     * @ORM\JoinColumn(nullable=false)
     */
    private $store;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $onHand;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getVariant(): ?ProductVariant
    {
        return $this->variant;
    }

    public function setVariant(?ProductVariant $variant): self
    {
        $this->variant = $variant;

        return $this;
    }

    public function getStore(): ?Store
    {
        return $this->store;
    }

    public function setStore(?Store $store): self
    {
        $this->store = $store;

        return $this;
    }

    public function getOnHand(): ?int
    {
        return $this->onHand;
    }

    public function setOnHand(?int $onHand): self
    {
        $this->onHand = $onHand;

        return $this;
    }
}
