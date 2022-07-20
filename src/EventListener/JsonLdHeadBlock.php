<?php

namespace App\EventListener;

use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Sonata\BlockBundle\Event\BlockEvent;
use Sonata\BlockBundle\Model\Block;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

final class JsonLdHeadBlock
{
    private string $template;
    private $router;
    private $cacheManager;

    public function __construct(string $template, RouterInterface $router, CacheManager $cacheManager)
    {
        $this->template = $template;
        $this->router = $router;
        $this->cacheManager = $cacheManager;
    }

    public function onBlockEvent(BlockEvent $event): void
    {
        $localCode = 'fr_FR';

        //https://developers.google.com/search/docs/advanced/structured-data/product?hl=fr#json-ld
        $snippets = [];
        if($product = $event->getSetting('product'))
        {
            $snippet = [
                '@context' => 'http://schema.org',
                '@type' => 'Product',
                'name' => $product->getName(),
                'sku' => $product->getCode(),
            ];

            if(($description = $product->getDescription()) && !empty($description))
            {
                $snippet['description'] = $description;
            }
            if($images = $product->getOrderedImages())
            {
                foreach($images as $image)
                {
                    if($image->getPath())
                    {
                        $snippet['image'][] = $this->cacheManager->getBrowserPath((string) $image->getPath(), 'sylius_shop_product_large_thumbnail');
                    }
                }
            }
            //$snippet['availability'] = $this->getAvailability($product, $simple_id);
            //$snippet['rating'] = $this->getProductRatings($product);
            
            /* add for Chullanka */
            if($brand = $product->getBrand())
            {
                $brandUrl = $this->router->generate(
                    'brand_view',
                    ['code' => $brand->getCode()],
                    UrlGeneratorInterface::ABSOLUTE_URL
                );

                $snippet['brand'] = [
                    '@type' => 'Brand',
            	    'name' => $brand->getName(),
                    'url' => $brandUrl
                ];
            	//$snippet['brand']['url'] = $brand->getBrandUrl();
            }

            $url = $this->router->generate(
                'sylius_shop_product_show',
                ['slug' => $product->getSlug(), '_locale' => $localCode],
                UrlGeneratorInterface::ABSOLUTE_URL
            );

            $totalQty = $product->getTotalQuantities();
            
            // offers
            $snippet['offers'] = [
                '@type' => 'Offer',
                'url'=> $url,
                //'itemCondition' => 'https://schema.org/NewCondition',
                'availability' => ((bool)$totalQty) ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
                'priceCurrency' => 'EUR'
            ];

            $variants = $product->getVariants();
            if($variant = $variants->first())
            {
                if($channelPricing = $variant->getChannelPricings()->first())
                {
                    $snippet['offers']['price'] = $channelPricing->getPrice() / 100;
                }
            }

            $productReviews = $product->getReviews();
            if(count($productReviews))
            {
                $reviewCount = 0;
                $bestRating = 0;
                $totalRating = 0;
                foreach($productReviews as $review)
                {
                    if($review->getStatus() != 'accepted') continue;

                    $rating = $review->getRating();
                    $bestRating = max($bestRating, $rating);
                    $totalRating += $rating;
                    $reviewCount++;
                }
                $averageRating = $totalRating / $reviewCount;
                
                // aggregateRating
                $snippet['aggregateRating'] = [
                    '@type' => 'Review',
                    'ratingValue' => $averageRating,
                    'reviewCount' => $reviewCount,
                ];
            }

            $snippets[] = json_encode($snippet);
        }

        $block = new Block();
        $block->setId(uniqid('', true));
        $block->setType('sonata.block.service.template');
        $block->setSettings(array_replace($event->getSettings(), [
            'template' => $this->template,
            'resources' => ['snippets' => $snippets]
        ]));
        $event->addBlock($block);
    }
}
