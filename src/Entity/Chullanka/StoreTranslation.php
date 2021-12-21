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
    private $description;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $joinus;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getJoinus(): ?string
    {
        return $this->joinus;
    }

    public function setJoinus(?string $joinus): self
    {
        $this->joinus = $joinus;

        return $this;
    }
}
