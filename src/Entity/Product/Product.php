<?php

declare(strict_types=1);

namespace App\Entity\Product;

use Doctrine\ORM\Mapping as ORM;
use Loevgaard\SyliusBrandPlugin\Model\ProductInterface as LoevgaardSyliusBrandPluginProductInterface;
use Loevgaard\SyliusBrandPlugin\Model\ProductTrait as LoevgaardSyliusBrandPluginProductTrait;
use Sylius\Component\Core\Model\Product as BaseProduct;
use Sylius\Component\Product\Model\ProductTranslationInterface;

/**
 * @ORM\Entity
 * @ORM\Table(name="sylius_product")
 */
class Product extends BaseProduct implements LoevgaardSyliusBrandPluginProductInterface
{
    use LoevgaardSyliusBrandPluginProductTrait;
    
    protected function createTranslation(): ProductTranslationInterface
    {
        return new ProductTranslation();
    }
}
