<?php

namespace App\Entity\Chullanka;

use App\Entity\Product\Product;
use App\Repository\Chullanka\FaqRepository;
use Doctrine\ORM\Mapping as ORM;
use Sylius\Component\Resource\Model\ResourceInterface;
use Sylius\Component\Resource\Model\TranslatableInterface;
use Sylius\Component\Resource\Model\TranslatableTrait;
use Sylius\Component\Resource\Model\TranslationInterface;

/**
 * @ORM\Entity(repositoryClass=FaqRepository::class)
 * @ORM\Table(name="nan_chk_faq")
 */
class Faq implements ResourceInterface, TranslatableInterface
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
     * @ORM\ManyToOne(targetEntity=Product::class, inversedBy="faqs")
     * @ORM\JoinColumn(nullable=false)
     */
    private $product;

    public function __construct()
    {
        $this->initializeTranslationsCollection();
    }

    public function __toString()
    {
        return "FAQ #" . $this->id;
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

    protected function createTranslation(): TranslationInterface
    {
        return new FaqTranslation();
    }

    public function getQuestion(): ?string
    {
        return $this->getTranslation()->getQuestion();
    }

    public function setQuestion(string $question): self
    {
        $this->getTranslation()->setQuestion($question);

        return $this;
    }

    public function getAnswer(): ?string
    {
        return $this->getTranslation()->getAnswer();
    }

    public function setAnswer(string $answer): self
    {
        $this->getTranslation()->setAnswer($answer);

        return $this;
    }
}
