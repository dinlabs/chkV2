<?php

namespace App\Entity\Chullanka;

use App\Entity\Addressing\Address;
use App\Entity\Customer\Customer;
use App\Entity\Order\Order;
use App\Repository\Chullanka\RmaRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Sylius\Component\Resource\Model\ResourceInterface;

/**
 * @ORM\Entity(repositoryClass=RmaRepository::class)
 * @ORM\Table(name="nan_chk_rma")
 */
class Rma implements ResourceInterface
{
    use TimestampableEntity;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Order::class, inversedBy="rmas")
     * @ORM\JoinColumn(nullable=false)
     */
    private $order;

    /**
     * @ORM\ManyToOne(targetEntity=Customer::class, inversedBy="rmas")
     * @ORM\JoinColumn(nullable=false)
     */
    private $customer;

    /**
     * @ORM\ManyToOne(targetEntity=Address::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $address;

    /**
     * @ORM\Column(type="string", length=20, nullable=true)
     */
    private $number;

    /**
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    private $phone_number;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $customer_email;

    /**
     * @ORM\Column(type="string", length=35)
     */
    private $state;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $reception_at;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $return_at;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $public_comment;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $private_comment;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $return_slip;

    /**
     * @ORM\OneToMany(targetEntity=RmaProduct::class, mappedBy="rma", orphanRemoval=true)
     */
    private $rmaProducts;

    public function __construct()
    {
        $this->rmaProducts = new ArrayCollection();
    }

    public function __toString()
    {
        return 'rma-' . $this->id;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrder(): ?Order
    {
        return $this->order;
    }

    public function setOrder(?Order $order): self
    {
        $this->order = $order;

        return $this;
    }

    public function getCustomer(): ?Customer
    {
        return $this->customer;
    }

    public function setCustomer(?Customer $customer): self
    {
        $this->customer = $customer;

        return $this;
    }

    public function getAddress(): ?Address
    {
        return $this->address;
    }

    public function setAddress(?Address $address): self
    {
        $this->address = $address;

        return $this;
    }

    public function getNumber(): ?string
    {
        return $this->number;
    }

    public function setNumber(?string $number): self
    {
        $this->number = $number;

        return $this;
    }

    public function getPhoneNumber(): ?string
    {
        return $this->phone_number;
    }

    public function setPhoneNumber(?string $phone_number): self
    {
        $this->phone_number = $phone_number;

        return $this;
    }

    public function getCustomerEmail(): ?string
    {
        return $this->customer_email;
    }

    public function setCustomerEmail(?string $customer_email): self
    {
        $this->customer_email = $customer_email;

        return $this;
    }

    public function getState(): ?string
    {
        return $this->state;
    }

    public function setState(string $state): self
    {
        $this->state = $state;

        return $this;
    }

    public function getReceptionAt(): ?\DateTimeInterface
    {
        return $this->reception_at;
    }

    public function setReceptionAt(?\DateTimeInterface $reception_at): self
    {
        $this->reception_at = $reception_at;

        return $this;
    }

    public function getReturnAt(): ?\DateTimeInterface
    {
        return $this->return_at;
    }

    public function setReturnAt(?\DateTimeInterface $return_at): self
    {
        $this->return_at = $return_at;

        return $this;
    }

    public function getPublicComment(): ?string
    {
        return $this->public_comment;
    }

    public function setPublicComment(?string $public_comment): self
    {
        $this->public_comment = $public_comment;

        return $this;
    }

    public function getPrivateComment(): ?string
    {
        return $this->private_comment;
    }

    public function setPrivateComment(?string $private_comment): self
    {
        $this->private_comment = $private_comment;

        return $this;
    }

    public function getReturnSlip(): ?bool
    {
        return $this->return_slip;
    }

    public function setReturnSlip(?bool $return_slip): self
    {
        $this->return_slip = $return_slip;

        return $this;
    }

    /**
     * @return Collection|RmaProduct[]
     */
    public function getRmaProducts(): Collection
    {
        return $this->rmaProducts;
    }

    public function addRmaProduct(RmaProduct $rmaProduct): self
    {
        if (!$this->rmaProducts->contains($rmaProduct)) {
            $this->rmaProducts[] = $rmaProduct;
            $rmaProduct->setRma($this);
        }

        return $this;
    }

    public function removeRmaProduct(RmaProduct $rmaProduct): self
    {
        if ($this->rmaProducts->removeElement($rmaProduct)) {
            // set the owning side to null (unless already changed)
            if ($rmaProduct->getRma() === $this) {
                $rmaProduct->setRma(null);
            }
        }

        return $this;
    }
}
