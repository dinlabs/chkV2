<?php

namespace App\Entity\Chullanka;

use App\Entity\Product\Product;
use App\Repository\Chullanka\ComplementaryProductRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Sylius\Component\Resource\Model\ResourceInterface;
use Sylius\Component\Resource\Model\TranslatableInterface;
use Sylius\Component\Resource\Model\TranslatableTrait;
use Sylius\Component\Resource\Model\TranslationInterface;

/**
 * @ORM\Entity(repositoryClass=ComplementaryProductRepository::class)
 * @ORM\Table(name="nan_chk_complementary")
 */
class ComplementaryProduct implements ResourceInterface, TranslatableInterface
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
     * @ORM\OneToOne(targetEntity=Product::class, inversedBy="complementaryProduct", cascade={"persist", "remove"})
     */
    private $product;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $background;

    /** @var File|null */
    protected $background_file;

    /**
     * @ORM\ManyToOne(targetEntity=Chulli::class)
     */
    private $chulli;

    /**
     * @ORM\ManyToMany(targetEntity=Product::class)
     * @ORM\JoinTable(name="nan_chk_complementary_product")
     */
    private $products;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $show_from;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $show_to;

    public function __construct()
    {
        $this->initializeTranslationsCollection();
        $this->setCurrentLocale('fr_FR');// hack de YL pour forcer la locale !
        $this->products = new ArrayCollection();
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

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): self
    {
        $this->product = $product;

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

    public function getChulli(): ?Chulli
    {
        return $this->chulli;
    }

    public function setChulli(?Chulli $chulli): self
    {
        $this->chulli = $chulli;

        return $this;
    }

    protected function createTranslation(): TranslationInterface
    {
        return new ComplementaryProductTranslation();
    }

    public function getTitle(): ?string
    {
        return $this->getTranslation()->getTitle();
    }

    public function setTitle(?string $title): self
    {
        $this->getTranslation()->setTitle($title);

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
        }

        return $this;
    }

    public function removeProduct(Product $product): self
    {
        $this->products->removeElement($product);

        return $this;
    }

    public function getShowFrom(): ?\DateTimeInterface
    {
        return $this->show_from;
    }

    public function setShowFrom(?\DateTimeInterface $show_from): self
    {
        $this->show_from = $show_from;

        return $this;
    }

    public function getShowTo(): ?\DateTimeInterface
    {
        return $this->show_to;
    }

    public function setShowTo(?\DateTimeInterface $show_to): self
    {
        $this->show_to = $show_to;

        return $this;
    }

    public function showCond(): ?bool
    {
        return (is_null($this->show_from) && is_null($this->show_to)) 
            ? $this->enabled 
            : (
                $this->enabled
                && (time() >= $this->show_from)
                && (time() < $this->show_to)
            );
    }
}