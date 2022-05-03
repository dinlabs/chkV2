<?php

declare(strict_types=1);

namespace App\Entity\Taxonomy;

use App\Entity\Chullanka\Brand;
use App\Entity\Chullanka\Link;
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

    /**
     * @ORM\ManyToMany(targetEntity=Taxon::class)
     * @ORM\JoinTable(name="nan_chk_taxon_other_taxon")
     */
    private $other_taxons;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $blogfeedurl;

    /**
     * @ORM\Column(type="boolean")
     */
    private $univers;

    /**
     * @ORM\ManyToOne(targetEntity=Taxon::class)
     */
    private $redirection;

    /**
     * @ORM\OneToMany(targetEntity=Link::class, mappedBy="taxon", orphanRemoval=true)
     */
    private $sub_links;

    public function __construct()
    {
        parent::__construct();
        $this->top_brands = new ArrayCollection();
        $this->top_products = new ArrayCollection();
        $this->other_taxons = new ArrayCollection();
        $this->sub_links = new ArrayCollection();
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

    /**
     * @return Collection|Taxon[]
     */
    public function getOtherTaxons(): Collection
    {
        return $this->other_taxons;
    }

    public function addOtherTaxon(Taxon $otherTaxon): self
    {
        if (!$this->other_taxons->contains($otherTaxon)) {
            $this->other_taxons[] = $otherTaxon;
        }

        return $this;
    }

    public function removeOtherTaxon(Taxon $otherTaxon): self
    {
        $this->other_taxons->removeElement($otherTaxon);

        return $this;
    }

    public function getBlogfeedurl(): ?string
    {
        return $this->blogfeedurl;
    }

    public function setBlogfeedurl(?string $blogfeedurl): self
    {
        $this->blogfeedurl = $blogfeedurl;

        return $this;
    }

    public function isUnivers(): ?bool
    {
        return $this->univers;
    }

    public function setUnivers(bool $univers): self
    {
        $this->univers = $univers;

        return $this;
    }

    public function getRedirection(): ?self
    {
        return $this->redirection;
    }

    public function setRedirection(?self $redirection): self
    {
        $this->redirection = $redirection;

        return $this;
    }

    /**
     * @return Collection|Link[]
     */
    public function getSubLinks(): Collection
    {
        return $this->sub_links;
    }

    public function addSubLink(Link $subLink): self
    {
        if (!$this->sub_links->contains($subLink)) {
            $this->sub_links[] = $subLink;
            $subLink->setTaxon($this);
        }

        return $this;
    }

    public function removeSubLink(Link $subLink): self
    {
        if ($this->sub_links->removeElement($subLink)) {
            // set the owning side to null (unless already changed)
            if ($subLink->getTaxon() === $this) {
                $subLink->setTaxon(null);
            }
        }

        return $this;
    }
}
