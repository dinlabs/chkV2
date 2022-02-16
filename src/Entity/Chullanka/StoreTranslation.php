<?php

namespace App\Entity\Chullanka;

use App\Repository\Chullanka\StoreTranslationRepository;
use Doctrine\ORM\Mapping as ORM;
use Sylius\Component\Resource\Model\AbstractTranslation;
use Sylius\Component\Resource\Model\ResourceInterface;

/**
 * @ORM\Entity(repositoryClass=StoreTranslationRepository::class)
 * @ORM\Table(name="nan_chk_store_translation")
 */
class StoreTranslation extends AbstractTranslation implements ResourceInterface
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
    private $warning;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $opening_hours;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $description;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $director_note;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $advertising;

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

    public function getWarning(): ?string
    {
        return $this->warning;
    }

    public function setWarning(?string $warning): self
    {
        $this->warning = $warning;

        return $this;
    }

    public function getOpeningHours(): ?string
    {
        return $this->opening_hours;
    }

    public function setOpeningHours(?string $opening_hours): self
    {
        $this->opening_hours = $opening_hours;

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

    public function getDirectorNote(): ?string
    {
        return $this->director_note;
    }

    public function setDirectorNote(?string $director_note): self
    {
        $this->director_note = $director_note;

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
}
