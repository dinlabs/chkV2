<?php

declare(strict_types=1);

namespace App\Entity\Taxonomy;

use Doctrine\ORM\Mapping as ORM;
use Sylius\Component\Taxonomy\Model\TaxonTranslation as BaseTaxonTranslation;

/**
 * @ORM\Entity
 * @ORM\Table(name="sylius_taxon_translation")
 */
class TaxonTranslation extends BaseTaxonTranslation
{
    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $content;

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(?string $content): self
    {
        $this->content = $content;

        return $this;
    }
}
