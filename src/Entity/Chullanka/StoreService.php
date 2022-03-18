<?php

namespace App\Entity\Chullanka;

use App\Repository\Chullanka\StoreServiceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=StoreServiceRepository::class)
 * @ORM\Table(name="nan_chk_store_service")
 */
class StoreService
{
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
     * @ORM\Column(type="boolean")
     */
    private $show_home;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $title;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $thumbnail;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $content;

    /**
     * @ORM\ManyToMany(targetEntity=Store::class, inversedBy="services")
     * @ORM\JoinTable(name="nan_chk_store_to_service")
     */
    private $store;

    public function __construct()
    {
        $this->store = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEnabled(): ?bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): self
    {
        $this->enabled = $enabled;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getThumbnail(): ?string
    {
        return $this->thumbnail;
    }

    public function setThumbnail(?string $thumbnail): self
    {
        $this->thumbnail = $thumbnail;

        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(?string $content): self
    {
        $this->content = $content;

        return $this;
    }

    /**
     * @return Collection|Store[]
     */
    public function getStore(): Collection
    {
        return $this->store;
    }

    public function addStore(Store $store): self
    {
        if (!$this->store->contains($store)) {
            $this->store[] = $store;
        }

        return $this;
    }

    public function removeStore(Store $store): self
    {
        $this->store->removeElement($store);

        return $this;
    }

    public function getShowHome(): ?bool
    {
        return $this->show_home;
    }

    public function setShowHome(bool $show_home): self
    {
        $this->show_home = $show_home;

        return $this;
    }
}
