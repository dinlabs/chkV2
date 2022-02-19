<?php

namespace App\Entity\Chullanka;

use App\Entity\Product\Product;
use App\Repository\Chullanka\BrandRepository;
use Sylius\Component\Resource\Model\ResourceInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Sylius\Component\Resource\Model\TranslatableInterface;
use Sylius\Component\Resource\Model\TranslatableTrait;
use Sylius\Component\Resource\Model\TranslationInterface;

/**
 * @ORM\Entity(repositoryClass=BrandRepository::class)
 * @ORM\Table(name="nan_chk_brand")
 */
class Brand implements ResourceInterface, TranslatableInterface
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
     * @ORM\Column(type="string", length=255)
     */
    private $code;

    /** @var string */
    private $escode;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\OneToMany(targetEntity=Product::class, mappedBy="brand")
     */
    private $products;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $logo;

    /** @var File|null */
    protected $logo_file;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $top;

    /**
     * @ORM\Column(type="smallint", nullable=true)
     */
    private $top_position;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $background;

    /** @var File|null */
    protected $background_file;

    /**
     * @ORM\ManyToOne(targetEntity=Product::class)
     */
    private $top_product;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $product_background;

    /** @var File|null */
    protected $product_background_file;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $soc_facebook;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $soc_twitter;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $soc_instagram;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $soc_youtube;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $soc_pinterest;

    /**
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    private $tag_instagram;

    public function __construct()
    {
        $this->initializeTranslationsCollection();
        $this->setCurrentLocale('fr_FR');// hack de YL pour forcer la locale !
        $this->products = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->name;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function getEscode(): ?string
    {
        return str_replace('-', '', $this->code);
    }

    public function setCode(string $code): self
    {
        $this->code = $code;

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

    protected function createTranslation(): TranslationInterface
    {
        return new BrandTranslation();
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
    
    public function getDescription(): ?string
    {
        return $this->getTranslation()->getDescription();
    }

    public function setDescription(?string $description): self
    {
        $this->getTranslation()->setDescription($description);

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
    
    public function getSizeGuide(): ?string
    {
        return $this->getTranslation()->getSizeGuide();
    }

    public function setSizeGuide(?string $size_guide): self
    {
        return $this->getTranslation()->setSizeGuide($size_guide);

        return $this;
    }

    /**
     * @return Collection|Product[]
     */
    public function getProducts(): Collection
    {
        return $this->products;
    }

    public function addProduct(Product $product): self
    {
        if (!$this->products->contains($product)) {
            $this->products[] = $product;
            $product->setBrand($this);
        }

        return $this;
    }

    public function removeProduct(Product $product): self
    {
        if ($this->products->removeElement($product)) {
            // set the owning side to null (unless already changed)
            if ($product->getBrand() === $this) {
                $product->setBrand(null);
            }
        }

        return $this;
    }

    public function getLogo(): ?string
    {
        return $this->logo;
    }

    public function setLogo(?string $logo): self
    {
        $this->logo = $logo;

        return $this;
    }

    public function getLogoFile(): ?File
    {
        return $this->logo_file;
    }

    public function setLogoFile(?File $logo_file): void
    {
        $this->logo_file = $logo_file;
    }

    public function hasLogoFile(): bool
    {
        return null !== $this->logo_file;
    }

    public function getTop(): ?bool
    {
        return $this->top;
    }

    public function setTop(?bool $top): self
    {
        $this->top = $top;

        return $this;
    }

    public function getTopPosition(): ?int
    {
        return $this->top_position;
    }

    public function setTopPosition(?int $top_position): self
    {
        $this->top_position = $top_position;

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

    public function getSocFacebook(): ?string
    {
        return $this->soc_facebook;
    }

    public function setSocFacebook(?string $soc_facebook): self
    {
        $this->soc_facebook = $soc_facebook;

        return $this;
    }

    public function getSocTwitter(): ?string
    {
        return $this->soc_twitter;
    }

    public function setSocTwitter(?string $soc_twitter): self
    {
        $this->soc_twitter = $soc_twitter;

        return $this;
    }

    public function getSocInstagram(): ?string
    {
        return $this->soc_instagram;
    }

    public function setSocInstagram(?string $soc_instagram): self
    {
        $this->soc_instagram = $soc_instagram;

        return $this;
    }

    public function getSocYoutube(): ?string
    {
        return $this->soc_youtube;
    }

    public function setSocYoutube(?string $soc_youtube): self
    {
        $this->soc_youtube = $soc_youtube;

        return $this;
    }

    public function getSocPinterest(): ?string
    {
        return $this->soc_pinterest;
    }

    public function setSocPinterest(?string $soc_pinterest): self
    {
        $this->soc_pinterest = $soc_pinterest;

        return $this;
    }

    public function getTagInstagram(): ?string
    {
        return $this->tag_instagram;
    }

    public function setTagInstagram(?string $tag_instagram): self
    {
        $this->tag_instagram = $tag_instagram;

        return $this;
    }

    public function getTopProduct(): ?Product
    {
        return $this->top_product;
    }

    public function setTopProduct(?Product $top_product): self
    {
        $this->top_product = $top_product;

        return $this;
    }

    public function getProductBackground(): ?string
    {
        return $this->product_background;
    }

    public function setProductBackground(?string $product_background): self
    {
        $this->product_background = $product_background;

        return $this;
    }

    public function getProductBackgroundFile(): ?File
    {
        return $this->product_background_file;
    }

    public function setProductBackgroundFile(?File $product_background_file): void
    {
        $this->product_background_file = $product_background_file;
    }

    public function hasProductBackgroundFile(): bool
    {
        return null !== $this->product_background_file;
    }
}
