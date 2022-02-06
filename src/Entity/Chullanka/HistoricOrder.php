<?php

namespace App\Entity\Chullanka;

use App\Entity\Customer\Customer;
use App\Repository\Chullanka\HistoricOrderRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=HistoricOrderRepository::class)
 * @ORM\Table(name="nan_chk_historic_order")
 */
class HistoricOrder
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private $origin;

    /**
     * @ORM\Column(type="string", length=10)
     */
    private $order_id;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private $sku;

    /**
     * @ORM\Column(type="date")
     */
    private $order_date;

    /**
     * @ORM\ManyToOne(targetEntity=Customer::class, inversedBy="historicOrders")
     * @ORM\JoinColumn(nullable=false)
     */
    private $customer;

    /**
     * @ORM\Column(type="json")
     */
    private $items = [];

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $address;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $shipment;

    /**
     * @ORM\Column(type="integer")
     */
    private $shipment_price;

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    private $shipment_date;

    /**
     * @ORM\Column(type="integer")
     */
    private $total;

    /**
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    private $paymethod;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $invoice;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrigin(): ?string
    {
        return $this->origin;
    }

    public function setOrigin(string $origin): self
    {
        $this->origin = $origin;

        return $this;
    }

    public function getOrderId(): ?string
    {
        return $this->order_id;
    }

    public function setOrderId(string $order_id): self
    {
        $this->order_id = $order_id;

        return $this;
    }

    public function getSku(): ?string
    {
        return $this->sku;
    }

    public function setSku(string $sku): self
    {
        $this->sku = $sku;

        return $this;
    }

    public function getOrderDate(): ?\DateTimeInterface
    {
        return $this->order_date;
    }

    public function setOrderDate(\DateTimeInterface $order_date): self
    {
        $this->order_date = $order_date;

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

    public function getItems(): ?array
    {
        return $this->items;
    }

    public function setItems(array $items): self
    {
        $this->items = $items;

        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): self
    {
        $this->address = $address;

        return $this;
    }

    public function getShipment(): ?string
    {
        return $this->shipment;
    }

    public function setShipment(string $shipment): self
    {
        $this->shipment = $shipment;

        return $this;
    }

    public function getShipmentPrice(): ?int
    {
        return $this->shipment_price;
    }

    public function setShipmentPrice(int $shipment_price): self
    {
        $this->shipment_price = $shipment_price;

        return $this;
    }

    public function getShipmentDate(): ?\DateTimeInterface
    {
        return $this->shipment_date;
    }

    public function setShipmentDate(?\DateTimeInterface $shipment_date): self
    {
        $this->shipment_date = $shipment_date;

        return $this;
    }

    public function getTotal(): ?int
    {
        return $this->total;
    }

    public function setTotal(int $total): self
    {
        $this->total = $total;

        return $this;
    }

    public function getPaymethod(): ?string
    {
        return $this->paymethod;
    }

    public function setPaymethod(?string $paymethod): self
    {
        $this->paymethod = $paymethod;

        return $this;
    }

    public function getInvoice(): ?string
    {
        return $this->invoice;
    }

    public function setInvoice(?string $invoice): self
    {
        $this->invoice = $invoice;

        return $this;
    }
}
