<?php

namespace App\Entity\Chullanka;

use App\Repository\Chullanka\BrandTranslationRepository;
use Sylius\Component\Resource\Model\AbstractTranslation;
use Sylius\Component\Resource\Model\ResourceInterface;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=BrandTranslationRepository::class)
 * @ORM\Table(name="nan_chk_brand_translation")
 */
class BrandTranslation extends AbstractTranslation implements ResourceInterface
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $introduction;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $description;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $advertising;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $size_guide;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIntroduction(): ?string
    {
        return $this->introduction;
    }

    public function setIntroduction(?string $introduction): self
    {
        $this->introduction = $introduction;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getAdvertising(): ?string
    {
        return $this->advertising;
    }

    public function setAdvertising(?string $advertising): self
    {
        $this->advertising = $advertising;

        return $this;
    }

    public function getSizeGuide(): ?string
    {
        return $this->size_guide;
    }

    public function setSizeGuide(?string $size_guide): self
    {
        $this->size_guide = $size_guide;

        return $this;
    }
}
