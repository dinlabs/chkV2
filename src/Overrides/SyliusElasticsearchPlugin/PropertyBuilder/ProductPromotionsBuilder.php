<?php

/*
 * This file was created by developers working at BitBag
 * Do you need more information about us and what we do? Visit our https://bitbag.io website!
 * We are hiring developers from all over the world. Join us and start your new, exciting adventure and become part of us: https://bitbag.io/career
*/

declare(strict_types=1);

namespace App\Overrides\SyliusElasticsearchPlugin\PropertyBuilder;

use BitBag\SyliusElasticsearchPlugin\PropertyBuilder\AbstractBuilder;
use Elastica\Document;
use FOS\ElasticaBundle\Event\PostTransformEvent;
use Sylius\Component\Core\Model\ProductInterface;

final class ProductPromotionsBuilder extends AbstractBuilder
{
    public const PROPERTY_NAME = 'promotion';

    public function consumeEvent(PostTransformEvent $event): void
    {
        $this->buildProperty(
            $event,
            ProductInterface::class,
            function (ProductInterface $product, Document $document): void {
                $promotion = false;
                foreach ($product->getVariants() as $variant) {
                    foreach ($variant->getChannelPricings() as $channelPricing) {
                        if ($channelPricing->getPrice() !== $channelPricing->getOriginalPrice()) {
                            $promotion = true;
                            break;
                        }
                    }
                }
                $document->set(self::PROPERTY_NAME, $promotion);
            }
        );
    }
}
