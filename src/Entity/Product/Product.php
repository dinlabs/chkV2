<?php

declare(strict_types=1);

namespace App\Entity\Product;

use App\Entity\Chullanka\Brand;
use App\Entity\Chullanka\Chulltest;
use App\Entity\Chullanka\ComplementaryProduct;
use App\Entity\Chullanka\Faq;
use App\Entity\Chullanka\PackElement;
use App\Entity\Chullanka\Recall;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Sylius\Component\Core\Model\Product as BaseProduct;
use Sylius\Component\Product\Model\ProductAttributeValueInterface;
use Sylius\Component\Product\Model\ProductTranslationInterface;

/**
 * @ORM\Entity
 * @ORM\Table(name="sylius_product")
 */
class Product extends BaseProduct
{
    /**
     * @ORM\ManyToOne(targetEntity=Brand::class, inversedBy="products")
     */
    private $brand;
    /** @var string */
    private $esbrand;

    /**
     * @ORM\Column(type="boolean", name="is_pack", options={"default":false})
     */
    private $isPack = false;

    /**
     * @ORM\Column(type="smallint", nullable=true)
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

    /**
     * @ORM\OneToOne(targetEntity=ComplementaryProduct::class, mappedBy="product", cascade={"persist", "remove"})
     */
    private $complementaryProduct;

    /**
     * @ORM\OneToMany(targetEntity=Faq::class, mappedBy="product", orphanRemoval=true)
     */
    private $faqs;

    /**
     * @ORM\OneToMany(targetEntity=Recall::class, mappedBy="product")
     */
    private $recalls;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $new_from;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $new_to;

    public function __construct()
    {
        parent::__construct();
        $this->packElements = new ArrayCollection();
        $this->faqs = new ArrayCollection();
        $this->recalls = new ArrayCollection();
    }
    
    protected function createTranslation(): ProductTranslationInterface
    {
        return new ProductTranslation();
    }

    public function getBrand(): ?Brand
    {
        return $this->brand;
    }
    public function getEsbrand(): ?string
    {
        return $this->brand ? $this->brand->getEscode() : null;
    }

    public function setBrand(?Brand $brand): self
    {
        $this->brand = $brand;

        return $this;
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

    public function getComplementaryProduct(): ?ComplementaryProduct
    {
        return $this->complementaryProduct;
    }

    public function setComplementaryProduct(?ComplementaryProduct $complementaryProduct): self
    {
        // unset the owning side of the relation if necessary
        if ($complementaryProduct === null && $this->complementaryProduct !== null) {
            $this->complementaryProduct->setProduct(null);
        }

        // set the owning side of the relation if necessary
        if ($complementaryProduct !== null && $complementaryProduct->getProduct() !== $this) {
            $complementaryProduct->setProduct($this);
        }
        
        $this->complementaryProduct = $complementaryProduct;

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
            if($attribute->getValue() == '') continue;
            $attributesWithFallback[] = $this->getAttributeInDifferentLocale($attribute, $localeCode, $fallbackLocaleCode);
        }

        return new ArrayCollection($attributesWithFallback);
    }

    /**
     * @return Collection|Faq[]
     */
    public function getFaqs(): Collection
    {
        return $this->faqs;
    }

    public function getEnabledFaqs(): Collection
    {
        return $this->faqs->filter(
            function ($faq) { return $faq->isEnabled(); }
        );
    }

    public function addFaq(Faq $faq): self
    {
        if (!$this->faqs->contains($faq)) {
            $this->faqs[] = $faq;
            $faq->setProduct($this);
        }

        return $this;
    }

    public function removeFaq(Faq $faq): self
    {
        if ($this->faqs->removeElement($faq)) {
            // set the owning side to null (unless already changed)
            if ($faq->getProduct() === $this) {
                $faq->setProduct(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Recall[]
     */
    public function getRecalls(): Collection
    {
        return $this->recalls;
    }

    public function addRecall(Recall $recall): self
    {
        if (!$this->recalls->contains($recall)) {
            $this->recalls[] = $recall;
            $recall->setProduct($this);
        }

        return $this;
    }

    public function removeRecall(Recall $recall): self
    {
        if ($this->recalls->removeElement($recall)) {
            // set the owning side to null (unless already changed)
            if ($recall->getProduct() === $this) {
                $recall->setProduct(null);
            }
        }

        return $this;
    }

    public function getNewFrom(): ?\DateTimeInterface
    {
        return $this->new_from;
    }

    public function setNewFrom(?\DateTimeInterface $new_from): self
    {
        $this->new_from = $new_from;

        return $this;
    }

    public function getNewTo(): ?\DateTimeInterface
    {
        return $this->new_to;
    }

    public function setNewTo(?\DateTimeInterface $new_to): self
    {
        $this->new_to = $new_to;

        return $this;
    }

    public function isNew(): ?bool
    {
        $now = new \DateTime();
        return !is_null($this->new_from) 
            && !is_null($this->new_to)
            && ($now >= $this->new_from)
            && ($now < $this->new_to)
        ;
    }


    /** Twig */
    public function getLabels()
    {
        $labels = [];

        if($this->isNew())
        {
            $labels['new'] = 'new';
        }

        if($variant = $this->getVariants()->first())
        {
            if($channelPricing = $variant->getChannelPricings()->first())
            {
                $percent = $channelPricing->getPercentage();
                if(!empty($percent))
                {
                    $labels['promo'] = '-' . (string)$percent . '%';
                }
            }
        }
        
        if($pictoEco = $this->getAttributeByCodeAndLocale('picto_eco'))
        {
            $value = $pictoEco->getValue();
            $conf = $pictoEco->getAttribute()->getConfiguration();
            $choices = $conf['choices'];
            foreach($choices as $key => $choice)
            {
                if(in_array($key, $value) && ($choice['fr_FR'] == 3))
                {
                    $labels['eco'] = 'eco';
                    break;
                }
            }
        }

        if($countryOfManufacture = $this->getAttributeByCodeAndLocale('country_of_manufacture'))
        {
            $country = $countryOfManufacture->getValue();
            if(strtoupper($country) == 'FR') $labels['madeinfrance'] = 'Made in France';
        }
        
        return $labels;
    }

    public function getTags()
    {
        $tags = [];

        if($cycleVie = $this->getAttributeByCodeAndLocale('cycle_vie'))
        {
            $cycleVie = $cycleVie->getValue();
            if($cycleVie == 3) $tags['ventesprivees'] = 'Ventes privées';
            if($cycleVie == 4) $tags['soldes'] = 'Soldes';
            if($cycleVie == 5) $tags['finserie'] = 'Fin de série';
        }

        if($this->getChulltest())
        {
            $tags['tested'] = 'Testé';
        }

        return $tags;
    }
}