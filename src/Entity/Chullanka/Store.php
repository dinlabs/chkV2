<?php

namespace App\Entity\Chullanka;

use App\Entity\Customer\Customer;
use App\Entity\Product\Product;
use App\Entity\Taxonomy\Taxon;
use App\Repository\Chullanka\StoreRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Sylius\Component\Resource\Model\ResourceInterface;
use Sylius\Component\Resource\Model\TranslatableInterface;
use Sylius\Component\Resource\Model\TranslatableTrait;
use Sylius\Component\Resource\Model\TranslationInterface;

/**
 * @ORM\Entity(repositoryClass=StoreRepository::class)
 * @ORM\Table(name="nan_chk_store")
 */
class Store implements ResourceInterface, TranslatableInterface
{
    use TranslatableTrait {
        __construct as private initializeTranslationsCollection;
    }

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="boolean")
     */
    private $enabled;

    /**
     * @ORM\Column(type="boolean", options={"default":"0"})
     */
    private $warehouse;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=50)
     */
    private $code;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $street;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private $postcode;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $city;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $latitude;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $longitude;

    /**
     * @ORM\Column(type="string", length=20, nullable=true)
     */
    private $phone_number;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $email;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $background;

    /** @var File|null */
    protected $background_file;

    /**
     * @ORM\OneToMany(targetEntity=Stock::class, mappedBy="store", orphanRemoval=true)
     */
    private $stocks;

    /**
     * @ORM\ManyToMany(targetEntity=Customer::class, mappedBy="favoriteStores")
     */
    private $customers;

    /**
     * @ORM\OneToMany(targetEntity=Chulli::class, mappedBy="store")
     */
    private $chullis;

    /** @var Chulli */
    private $director;

    /**
     * @ORM\ManyToMany(targetEntity=StoreService::class, mappedBy="stores")
     * @ORM\JoinTable(name="nan_chk_store_to_service")
     */
    private $services;

    /**
     * @ORM\ManyToMany(targetEntity=Product::class)
     * @ORM\JoinTable(name="nan_chk_store_exclusive_product")
     */
    private $exclusive_products;

    /**
     * @ORM\ManyToMany(targetEntity=Product::class)
     * @ORM\JoinTable(name="nan_chk_store_other_product")
     */
    private $other_products;

    /**
     * @ORM\ManyToMany(targetEntity=Taxon::class)
     * @ORM\JoinTable(name="nan_chk_store_taxon")
     */
    private $taxons;

    public function __construct()
    {
        $this->initializeTranslationsCollection();
        $this->stocks = new ArrayCollection();
        $this->customers = new ArrayCollection();
        $this->chullis = new ArrayCollection();
        $this->services = new ArrayCollection();
        $this->exclusive_products = new ArrayCollection();
        $this->other_products = new ArrayCollection();
        $this->taxons = new ArrayCollection();
    }

    public function __toString()
    {
        return $this->name;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function isEnabled(): ?bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): self
    {
        $this->enabled = $enabled;

        return $this;
    }

    public function isWarehouse(): ?bool
    {
        return $this->warehouse;
    }

    public function setWarehouse(bool $warehouse): self
    {
        $this->warehouse = $warehouse;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;

        return $this;
    }

    protected function createTranslation(): TranslationInterface
    {
        return new StoreTranslation();
    }

    public function getIntroduction(): ?string
    {
        return $this->getTranslation()->getIntroduction();
    }

    public function setIntroduction(?string $introduction): self
    {
        $this->getTranslation()->setIntroduction($introduction);

        return $this;
    }

    public function getWarning(): ?string
    {
        return $this->getTranslation()->getWarning();
    }

    public function setWarning(?string $warning): self
    {
        $this->getTranslation()->setWarning($warning);

        return $this;
    }

    public function getOpeningHours(): ?string
    {
        return $this->getTranslation()->getOpeningHours();
    }

    public function setOpeningHours(string $opening_hours): self
    {
        $this->getTranslation()->setOpeningHours($opening_hours);

        return $this;
    }

    public function getDirectorNote(): ?string
    {
        return $this->getTranslation()->getDirectorNote();
    }

    public function setDirectorNote(?string $director_note): self
    {
        $this->getTranslation()->setDirectorNote($director_note);

        return $this;
    }

    public function getAdvertising(): ?string
    {
        return $this->getTranslation()->getAdvertising();
    }

    public function setAdvertising(?string $advertising): self
    {
        $this->getTranslation()->setAdvertising($advertising);

        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->street . ' – ' . $this->postcode . ' ' . $this->city;
    }

    public function getStreet(): ?string
    {
        return $this->street;
    }

    public function setStreet(string $street): self
    {
        $this->street = $street;

        return $this;
    }

    public function getPostcode(): ?string
    {
        return $this->postcode;
    }

    public function setPostcode(string $postcode): self
    {
        $this->postcode = $postcode;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(string $city): self
    {
        $this->city = $city;

        return $this;
    }

    public function getLatitude(): ?float
    {
        return $this->latitude;
    }

    public function setLatitude(?float $latitude): self
    {
        $this->latitude = $latitude;

        return $this;
    }

    public function getLongitude(): ?float
    {
        return $this->longitude;
    }

    public function setLongitude(?float $longitude): self
    {
        $this->longitude = $longitude;

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

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getBackground(): ?string
    {
        return $this->background;
    }

    public function setBackground(?string $background): self
    {
        $this->background = $background;

        return $this;
    }

    public function getBackgroundFile(): ?File
    {
        return $this->background_file;
    }

    public function setBackgroundFile(?File $background_file): void
    {
        $this->background_file = $background_file;
    }

    public function hasBackgroundFile(): bool
    {
        return null !== $this->background_file;
    }

    public function getDescription(): ?string
    {
        return $this->getTranslation()->getDescription();
    }

    public function setDescription(?string $description): self
    {
        $this->getTranslation()->setDescription($description);

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
            $stock->setStore($this);
        }

        return $this;
    }

    public function removeStock(Stock $stock): self
    {
        if ($this->stocks->removeElement($stock)) {
            // set the owning side to null (unless already changed)
            if ($stock->getStore() === $this) {
                $stock->setStore(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Customer[]
     */
    public function getCustomers(): Collection
    {
        return $this->customers;
    }

    public function addCustomer(Customer $customer): self
    {
        if (!$this->customers->contains($customer)) {
            $this->customers[] = $customer;
            $customer->addFavoriteStore($this);
        }

        return $this;
    }

    public function removeCustomer(Customer $customer): self
    {
        if ($this->customers->removeElement($customer)) {
            $customer->removeFavoriteStore($this);
        }

        return $this;
    }

    /**
     * @return Collection|Chulli[]
     */
    public function getChullis(): Collection
    {
        return $this->chullis;
    }

    public function addChulli(Chulli $chulli): self
    {
        if (!$this->chullis->contains($chulli)) {
            $this->chullis[] = $chulli;
            $chulli->setStore($this);
        }

        return $this;
    }

    public function removeChulli(Chulli $chulli): self
    {
        if ($this->chullis->removeElement($chulli)) {
            // set the owning side to null (unless already changed)
            if ($chulli->getStore() === $this) {
                $chulli->setStore(null);
            }
        }

        return $this;
    }

    public function getDirector()
    {
        foreach($this->chullis as $chulli)
        {
            if($chulli->isLeader()) return $chulli;
        }
        return false;
    }

    /**
     * @return Collection|StoreService[]
     */
    public function getServices(): Collection
    {
        return $this->services;
    }

    public function addService(StoreService $service): self
    {
        if (!$this->services->contains($service)) {
            $this->services[] = $service;
            $service->addStore($this);
        }

        return $this;
    }

    public function removeService(StoreService $service): self
    {
        if ($this->services->removeElement($service)) {
            $service->removeStore($this);
        }

        return $this;
    }

    /**
     * @return Collection|Product[]
     */
    public function getExclusiveProducts(): Collection
    {
        return $this->exclusive_products;
    }

    public function addExclusiveProduct(Product $exclusiveProduct): self
    {
        if (!$this->exclusive_products->contains($exclusiveProduct)) {
            $this->exclusive_products[] = $exclusiveProduct;
        }

        return $this;
    }

    public function removeExclusiveProduct(Product $exclusiveProduct): self
    {
        $this->exclusive_products->removeElement($exclusiveProduct);

        return $this;
    }

    /**
     * @return Collection|Product[]
     */
    public function getOtherProducts(): Collection
    {
        return $this->other_products;
    }

    public function addOtherProduct(Product $otherProduct): self
    {
        if (!$this->other_products->contains($otherProduct)) {
            $this->other_products[] = $otherProduct;
        }

        return $this;
    }

    public function removeOtherProduct(Product $otherProduct): self
    {
        $this->other_products->removeElement($otherProduct);

        return $this;
    }

    /**
     * @return Collection|Taxon[]
     */
    public function getTaxons(): Collection
    {
        return $this->taxons;
    }

    public function addTaxon(Taxon $taxon): self
    {
        if (!$this->taxons->contains($taxon)) {
            $this->taxons[] = $taxon;
        }

        return $this;
    }

    public function removeTaxon(Taxon $taxon): self
    {
        $this->taxons->removeElement($taxon);

        return $this;
    }
}
