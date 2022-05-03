<?php

namespace App\Entity\Chullanka;

use App\Entity\Taxonomy\Taxon;
use App\Repository\Chullanka\LinkRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=LinkRepository::class)
 * @ORM\Table(name="nan_chk_link")
 */
class Link
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $title;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $url;

    /**
     * @ORM\ManyToOne(targetEntity=Taxon::class, inversedBy="sub_links")
     * @ORM\JoinColumn(nullable=false)
     */
    private $taxon;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function __toString()
    {
        return $this->title;
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

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(string $url): self
    {
        $this->url = $url;

        return $this;
    }

    public function getTaxon(): ?Taxon
    {
        return $this->taxon;
    }

    public function setTaxon(?Taxon $taxon): self
    {
        $this->taxon = $taxon;

        return $this;
    }
}
