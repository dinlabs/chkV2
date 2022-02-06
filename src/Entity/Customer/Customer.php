<?php

declare(strict_types=1);

namespace App\Entity\Customer;

use App\Entity\Chullanka\HistoricOrder;
use App\Entity\Chullanka\Rma;
use App\Entity\Chullanka\Sport;
use App\Entity\Chullanka\Store;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Sylius\Component\Core\Model\Customer as BaseCustomer;

/**
 * @ORM\Entity
 * @ORM\Table(name="sylius_customer")
 */
class Customer extends BaseCustomer
{
    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $chullpoints;

    /**
     * @ORM\ManyToMany(targetEntity=Store::class, inversedBy="customers")
     * @ORM\JoinTable(name="nan_chk_customer_store")
     */
    private $favoriteStores;

    /**
     * @ORM\ManyToMany(targetEntity=Sport::class, inversedBy="customers")
     * @ORM\JoinTable(name="nan_chk_customer_sport")
     */
    private $favoriteSports;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $licence_name;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $licence_number;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $licence_file;

    /**
     * @ORM\OneToMany(targetEntity=Rma::class, mappedBy="customer")
     */
    private $rmas;

    /**
     * @ORM\OneToMany(targetEntity=HistoricOrder::class, mappedBy="customer")
     */
    private $historicOrders;

    public function __construct()
    {
        parent::__construct();
        $this->favoriteStores = new ArrayCollection();
        $this->favoriteSports = new ArrayCollection();
        $this->rmas = new ArrayCollection();
        $this->historicOrders = new ArrayCollection();
    }

    public function getChullpoints(): ?int
    {
        return $this->chullpoints;
    }
    
    public function setChullpoints(?int $chullpoints): self
    {
        $this->chullpoints = $chullpoints;
        
        return $this;
    }

    /**
     * @return Collection|Store[]
     */
    public function getFavoriteStores(): Collection
    {
        return $this->favoriteStores;
    }

    public function addFavoriteStore(Store $favoriteStore): self
    {
        if (!$this->favoriteStores->contains($favoriteStore)) {
            $this->favoriteStores[] = $favoriteStore;
        }

        return $this;
    }

    public function removeFavoriteStore(Store $favoriteStore): self
    {
        $this->favoriteStores->removeElement($favoriteStore);

        return $this;
    }

    /**
     * @return Collection|Sport[]
     */
    public function getFavoriteSports(): Collection
    {
        return $this->favoriteSports;
    }

    public function addFavoriteSport(Sport $favoriteSport): self
    {
        if (!$this->favoriteSports->contains($favoriteSport)) {
            $this->favoriteSports[] = $favoriteSport;
        }

        return $this;
    }

    public function removeFavoriteSport(Sport $favoriteSport): self
    {
        $this->favoriteSports->removeElement($favoriteSport);

        return $this;
    }

    public function getLicenceName(): ?string
    {
        return $this->licence_name;
    }

    public function setLicenceName(?string $licence_name): self
    {
        $this->licence_name = $licence_name;

        return $this;
    }

    public function getLicenceNumber(): ?string
    {
        return $this->licence_number;
    }

    public function setLicenceNumber(?string $licence_number): self
    {
        $this->licence_number = $licence_number;

        return $this;
    }

    public function getLicenceFile(): ?string
    {
        return $this->licence_file;
    }

    public function setLicenceFile(?string $licence_file): self
    {
        $this->licence_file = $licence_file;

        return $this;
    }

    public function getCompletedOrders()
    {
        $completedOrders = $this->orders->filter(function($order) {
            //return $order->getState() == 'new';
            return $order->getCheckoutState() == 'completed';
        });
        return $completedOrders;
    }

    public function hasOrder($order): bool
    {
        return $this->orders->contains($order);
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
            $rma->setCustomer($this);
        }

        return $this;
    }

    public function removeRma(Rma $rma): self
    {
        if ($this->rmas->removeElement($rma)) {
            // set the owning side to null (unless already changed)
            if ($rma->getCustomer() === $this) {
                $rma->setCustomer(null);
            }
        }

        return $this;
    }
    
    
    /**
     * for Twig templates
     */
    public function reduction()
    {
        return floor($this->chullpoints / 500) * -10;
    }

    /**
     * @return Collection|HistoricOrder[]
     */
    public function getHistoricOrders(): Collection
    {
        return $this->historicOrders;
    }

    public function addHistoricOrder(HistoricOrder $historicOrder): self
    {
        if (!$this->historicOrders->contains($historicOrder)) {
            $this->historicOrders[] = $historicOrder;
            $historicOrder->setCustomer($this);
        }

        return $this;
    }

    public function removeHistoricOrder(HistoricOrder $historicOrder): self
    {
        if ($this->historicOrders->removeElement($historicOrder)) {
            // set the owning side to null (unless already changed)
            if ($historicOrder->getCustomer() === $this) {
                $historicOrder->setCustomer(null);
            }
        }

        return $this;
    }
}
