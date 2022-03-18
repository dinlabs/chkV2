<?php

namespace App\Entity\Chullanka;

use App\Entity\Chullanka\Chulli;
use App\Entity\Product\Product;
use App\Repository\Chullanka\ChulltestRepository;
use Doctrine\ORM\Mapping as ORM;
use Sylius\Component\Resource\Model\ResourceInterface;
use Sylius\Component\Resource\Model\TranslatableInterface;
use Sylius\Component\Resource\Model\TranslatableTrait;
use Sylius\Component\Resource\Model\TranslationInterface;

/**
 * @ORM\Entity(repositoryClass=ChulltestRepository::class)
 * @ORM\Table(name="nan_chk_chulltest")
 */
class Chulltest implements ResourceInterface, TranslatableInterface
{
    use TranslatableTrait {
        __construct as private initializeTranslationsCollection;
    }

    public function __construct()
    {
        $this->initializeTranslationsCollection();
        $this->setCurrentLocale('fr_FR');// hack de YL pour forcer la locale !
    }

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\OneToOne(targetEntity=Product::class, inversedBy="chulltest", cascade={"persist", "remove"})
     */
    private $product;

    /**
     * @ORM\ManyToOne(targetEntity=Chulli::class, inversedBy="tests")
     */
    private $chulli;

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    private $date;

    /**
     * @ORM\Column(type="smallint", nullable=true)
     */
    private $note;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getChulli(): ?Chulli
    {
        return $this->chulli;
    }

    public function setChulli(?Chulli $chulli): self
    {
        $this->chulli = $chulli;

        return $this;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(?\DateTimeInterface $date): self
    {
        $this->date = $date;

        return $this;
    }

    public function getHeadline(): ?string
    {
        return $this->getTranslation()->getHeadline();
    }

    public function setHeadline(string $headline): self
    {
        $this->getTranslation()->setHeadline($headline);

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

    public function getSumup(): ?string
    {
        return $this->getTranslation()->getSumup();
    }

    public function setSumup(?string $sumup): self
    {
        $this->getTranslation()->setSumup($sumup);

        return $this;
    }

    public function getPros(): ?string
    {
        return $this->getTranslation()->getPros();
    }

    public function setPros(?string $pros): self
    {
        $this->getTranslation()->setPros($pros);

        return $this;
    }

    public function getCons(): ?string
    {
        return $this->getTranslation()->getCons();
    }

    public function setCons(?string $cons): self
    {
        $this->getTranslation()->setCons($cons);

        return $this;
    }

    public function getNote(): ?int
    {
        return $this->note;
    }

    public function setNote(?int $note): self
    {
        $this->note = $note;

        return $this;
    }    

    protected function createTranslation(): TranslationInterface
    {
        return new ChulltestTranslation();
    }
}