<?php

declare(strict_types=1);

namespace App\Entity\Product;

use Doctrine\ORM\Mapping as ORM;
use Sylius\Component\Attribute\Model\AttributeTranslationInterface;
use Sylius\Component\Product\Model\ProductAttribute as BaseProductAttribute;

/**
 * @ORM\Entity
 * @ORM\Table(name="sylius_product_attribute")
 */
class ProductAttribute extends BaseProductAttribute
{
    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $filterable;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $preopened;

    protected function createTranslation(): AttributeTranslationInterface
    {
        return new ProductAttributeTranslation();
    }

    public function getFilterable(): ?bool
    {
        return $this->filterable;
    }

    public function setFilterable(?bool $filterable): self
    {
        $this->filterable = $filterable;

        return $this;
    }

    public function getPreopened(): ?bool
    {
        return $this->preopened;
    }

    public function setPreopened(?bool $preopened): self
    {
        $this->preopened = $preopened;

        return $this;
    }
}
