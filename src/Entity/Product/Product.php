<?php

declare(strict_types=1);

namespace App\Entity\Product;

use App\Entity\Chullanka\Chulltest;
use App\Entity\Chullanka\PackElement;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Loevgaard\SyliusBrandPlugin\Model\ProductInterface as LoevgaardSyliusBrandPluginProductInterface;
use Loevgaard\SyliusBrandPlugin\Model\ProductTrait as LoevgaardSyliusBrandPluginProductTrait;
use Sylius\Component\Core\Model\Product as BaseProduct;
use Sylius\Component\Product\Model\ProductAttributeValueInterface;
use Sylius\Component\Product\Model\ProductTranslationInterface;

/**
 * @ORM\Entity
 * @ORM\Table(name="sylius_product")
 */
class Product extends BaseProduct implements LoevgaardSyliusBrandPluginProductInterface
{
    use LoevgaardSyliusBrandPluginProductTrait;

    /**
     * @ORM\Column(type="boolean", name="is_pack", options={"default":false})
     */
    private $isPack = false;

    /**
     * @ORM\Column(type="smallint", nullable=true, columnDefinition="TINYINT(1) DEFAULT NULL")
     */
    private $mounting;
    
    /**
     * @ORM\OneToMany(targetEntity=PackElement::class, mappedBy="parent", orphanRemoval=true)
     */
    private $packElements;

    /**
     * @ORM\OneToOne(targetEntity=Chulltest::class, mappedBy="product", cascade={"persist", "remove"})
     */
    private $chulltest;

    public function __construct()
    {
        parent::__construct();
        $this->packElements = new ArrayCollection();
    }
    
    protected function createTranslation(): ProductTranslationInterface
    {
        return new ProductTranslation();
    }
    
    public function getIsPack(): ?bool
    {
        return $this->isPack;
    }

    public function setIsPack(bool $isPack): self
    {
        $this->isPack = $isPack;

        return $this;
    }

    public function getMounting(): ?int
    {
        return $this->mounting;
    }

    public function setMounting(?int $mounting): self
    {
        $this->mounting = $mounting;

        return $this;
    }
    
    /**
     * @return Collection|PackElement[]
     */
    public function getPackElements(): Collection
    {
        return $this->packElements;
    }

    public function addPackElement(PackElement $packElement): self
    {
        if (!$this->packElements->contains($packElement)) {
            $this->packElements[] = $packElement;
            $packElement->setParent($this);
        }

        return $this;
    }

    public function removePackElement(PackElement $packElement): self
    {
        if ($this->packElements->removeElement($packElement)) {
            // set the owning side to null (unless already changed)
            if ($packElement->getParent() === $this) {
                $packElement->setParent(null);
            }
        }

        return $this;
    }

    public function getChulltest(): ?Chulltest
    {
        return $this->chulltest;
    }

    public function setChulltest(?Chulltest $chulltest): self
    {
        // unset the owning side of the relation if necessary
        if ($chulltest === null && $this->chulltest !== null) {
            $this->chulltest->setProduct(null);
        }

        // set the owning side of the relation if necessary
        if ($chulltest !== null && $chulltest->getProduct() !== $this) {
            $chulltest->setProduct($this);
        }
        
        $this->chulltest = $chulltest;

        return $this;
    }

    /**
     * YL : ajout d'un tableau d'exclusions
     */
    public function getAttributesByLocale(
        string $localeCode,
        string $fallbackLocaleCode,
        ?string $baseLocaleCode = null,
        ?array $excludeCodes = []
    ): Collection {
        if (null === $baseLocaleCode || $baseLocaleCode === $fallbackLocaleCode) {
            $baseLocaleCode = $fallbackLocaleCode;
            $fallbackLocaleCode = null;
        }

        $attributes = $this->attributes->filter(
            function (ProductAttributeValueInterface $attribute) use ($baseLocaleCode) {
                return $attribute->getLocaleCode() === $baseLocaleCode || null === $attribute->getLocaleCode();
            }
        );

        $attributesWithFallback = [];
        foreach ($attributes as $attribute) {
            if(in_array($attribute->getCode(), $excludeCodes)) continue;
            $attributesWithFallback[] = $this->getAttributeInDifferentLocale($attribute, $localeCode, $fallbackLocaleCode);
        }

        return new ArrayCollection($attributesWithFallback);
    }
}