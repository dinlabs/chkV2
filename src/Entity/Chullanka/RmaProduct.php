<?php

namespace App\Entity\Chullanka;

use App\Entity\Order\OrderItem;
use App\Repository\Chullanka\RmaProductRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=RmaProductRepository::class)
 * @ORM\Table(name="nan_chk_rma_product")
 */
class RmaProduct
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Rma::class, inversedBy="rmaProducts")
     * @ORM\JoinColumn(nullable=false)
     */
    private $rma;

    /**
     * @ORM\ManyToOne(targetEntity=OrderItem::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $orderitem;

    /**
     * @ORM\Column(type="integer")
     */
    private $quantity;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $reason;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRma(): ?Rma
    {
        return $this->rma;
    }

    public function setRma(?Rma $rma): self
    {
        $this->rma = $rma;

        return $this;
    }

    public function getOrderitem(): ?OrderItem
    {
        return $this->orderitem;
    }

    public function setOrderitem(?OrderItem $orderitem): self
    {
        $this->orderitem = $orderitem;

        return $this;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): self
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function setReason(?string $reason): self
    {
        $this->reason = $reason;

        return $this;
    }
}
