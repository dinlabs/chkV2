<?php

declare(strict_types=1);

namespace App\Entity\Taxonomy;

use App\Entity\Chullanka\Brand;
use App\Entity\Product\Product;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Sylius\Component\Core\Model\Taxon as BaseTaxon;
use Sylius\Component\Taxonomy\Model\TaxonTranslationInterface;

/**
 * @ORM\Entity
 * @ORM\Table(name="sylius_taxon")
 */
class Taxon extends BaseTaxon
{
    /**
     * @ORM\ManyToMany(targetEntity=Brand::class, inversedBy="taxa")
     * @ORM\JoinTable(name="nan_chk_taxon_brand")
     */
    private $top_brands;

    /**
     * @ORM\ManyToMany(targetEntity=Product::class)
     * @ORM\JoinTable(name="nan_chk_taxon_top_product")
     */
    private $top_products;

    public function __construct()
    {
        parent::__construct();
        $this->top_brands = new ArrayCollection();
        $this->top_products = new ArrayCollection();
    }

    protected function createTranslation(): TaxonTranslationInterface
    {
        return new TaxonTranslation();
    }

    public function getContent(): ?string
    {
        return $this->getTranslation()->getContent();
    }

    public function setContent(?string $content): void
    {
        $this->getTranslation()->setContent($content);
    }

    /**
     * @return Collection|Brand[]
     */
    public function getTopBrands(): Collection
    {
        return $this->top_brands;
    }

    public function addTopBrand(Brand $topBrand): self
    {
        if (!$this->top_brands->contains($topBrand)) {
            $this->top_brands[] = $topBrand;
        }

        return $this;
    }

    public function removeTopBrand(Brand $topBrand): self
    {
        $this->top_brands->removeElement($topBrand);

        return $this;
    }

    /**
     * @return Collection|Product[]
     */
    public function getTopProducts(): Collection
    {
        return $this->top_products;
    }

    public function addTopProduct(Product $topProduct): self
    {
        if (!$this->top_products->contains($topProduct)) {
            $this->top_products[] = $topProduct;
        }

        return $this;
    }

    public function removeTopProduct(Product $topProduct): self
    {
        $this->top_products->removeElement($topProduct);

        return $this;
    }
}
