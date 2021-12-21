<?php

namespace App\Entity\Chullanka;

use App\Repository\Chullanka\ChulltestTranslationRepository;
use Doctrine\ORM\Mapping as ORM;
use Sylius\Component\Resource\Model\AbstractTranslation;
use Sylius\Component\Resource\Model\ResourceInterface;

/**
 * @ORM\Entity(repositoryClass=ChulltestTranslationRepository::class)
 * @ORM\Table(name="nan_chk_chulltest_translation")
 */
class ChulltestTranslation extends AbstractTranslation implements ResourceInterface
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
    private $pros;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $cons;

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

    public function getPros(): ?string
    {
        return $this->pros;
    }

    public function setPros(?string $pros): self
    {
        $this->pros = $pros;

        return $this;
    }

    public function getCons(): ?string
    {
        return $this->cons;
    }

    public function setCons(?string $cons): self
    {
        $this->cons = $cons;

        return $this;
    }
}
