<?php

declare(strict_types=1);

namespace App\Entity\Product;

use App\Entity\Chullanka\Chulltest;
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
     * @ORM\OneToOne(targetEntity=Chulltest::class, mappedBy="product", cascade={"persist", "remove"})
     */
    private $chulltest;
    
    protected function createTranslation(): ProductTranslationInterface
    {
        return new ProductTranslation();
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
