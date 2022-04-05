<?php

declare(strict_types=1);

namespace App\Overrides\SyliusFeedPlugin\FeedContext\GoogleShopping;

use InvalidArgumentException;
use App\Overrides\SyliusFeedPlugin\Model\Price;
use App\Overrides\SyliusFeedPlugin\Model\Product;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Setono\SyliusFeedPlugin\Feed\Model\Google\Shopping\Availability;
use Setono\SyliusFeedPlugin\Feed\Model\Google\Shopping\Condition;
#use Setono\SyliusFeedPlugin\Feed\Model\Google\Shopping\Price;
#use Setono\SyliusFeedPlugin\Feed\Model\Google\Shopping\Product;
use Setono\SyliusFeedPlugin\FeedContext\ContextList;
use Setono\SyliusFeedPlugin\FeedContext\ContextListInterface;
use Setono\SyliusFeedPlugin\FeedContext\ItemContextInterface;
use Setono\SyliusFeedPlugin\Model\BrandAwareInterface;
use Setono\SyliusFeedPlugin\Model\ColorAwareInterface;
use Setono\SyliusFeedPlugin\Model\ConditionAwareInterface;
use Setono\SyliusFeedPlugin\Model\GtinAwareInterface;
use Setono\SyliusFeedPlugin\Model\MpnAwareInterface;
use Setono\SyliusFeedPlugin\Model\SizeAwareInterface;
use Setono\SyliusFeedPlugin\Model\TaxonPathAwareInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\ImageInterface;
use Sylius\Component\Core\Model\ImagesAwareInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductTranslationInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Core\Model\TaxonInterface;
use Sylius\Component\Inventory\Checker\AvailabilityCheckerInterface;
use Sylius\Component\Locale\Model\LocaleInterface;
use Sylius\Component\Resource\Model\TranslatableInterface;
use Sylius\Component\Resource\Model\TranslationInterface;
use Sylius\Component\Taxonomy\Model\TaxonTranslationInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Webmozart\Assert\Assert;
use Setono\SyliusFeedPlugin\FeedContext\Google\Shopping\ProductItemContext as BaseProductItemContext;

class ProductItemContext extends BaseProductItemContext
{   
    private RouterInterface $router;

    private CacheManager $cacheManager;

    private AvailabilityCheckerInterface $availabilityChecker;

    public function __construct(
        RouterInterface $router,
        CacheManager $cacheManager,
        AvailabilityCheckerInterface $availabilityChecker
    ) {
        $this->router = $router;
        $this->cacheManager = $cacheManager;
        $this->availabilityChecker = $availabilityChecker;
    }

    public function getContextList(object $product, ChannelInterface $channel, LocaleInterface $locale): ContextListInterface
    {
        if (!$product instanceof ProductInterface) {
            throw new InvalidArgumentException(sprintf(
                'The class %s is not an instance of %s',
                get_class($product),
                ProductInterface::class
            ));
        }

        $excludeRootTaxon = false; // @todo Make it configurable
        if ($product instanceof TaxonPathAwareInterface) {
            $productType = $product->getTaxonPath($locale, $excludeRootTaxon);
        } else {
            $productType = $this->getProductType($product, $locale, $excludeRootTaxon);
        }

        /** @var ProductTranslationInterface|null $translation */
        $translation = $this->getTranslation($product, (string) $locale->getCode());
        $contextList = new ContextList();

        if($product->isEnabled() == false) return $contextList;// ajout Yannick

        foreach ($product->getVariants() as $variant) {
            if($variant->isEnabled() == false) continue;// ajout Yannick

            Assert::isInstanceOf($variant, ProductVariantInterface::class);
            $data = new Product();
            $data->setId((string)$variant->getId());
            $data->setItemGroupId($product->getCode());
            $data->setImageLink($this->getImageLink($product));
            $data->setAvailability($this->getAvailability($variant));

            [$price, $salePrice, $taxExclPrice, $taxPercent] = $this->getPrices($variant, $channel);
            $data->setPrice($price);
            $data->setSalePrice($salePrice);
            $data->setTaxExclPrice((float)$taxExclPrice);
            $data->setTaxPercent((string)($taxPercent * 100). '%');

            if (null !== $translation) {
                $data->setTitle($translation->getName());
                $data->setDescription($translation->getDescription());
                $data->setLink($this->getLink($locale, $translation));
            }

            $data->setCondition(
                $product instanceof ConditionAwareInterface ?
                    new Condition((string) $product->getCondition()) : Condition::new()
            );

            if (null !== $productType) {
                $data->setProductType($productType);
            }

            
            if ($variant instanceof BrandAwareInterface && $variant->getBrand() !== null) {
                $data->setBrand((string) $variant->getBrand());
            } elseif ($product instanceof BrandAwareInterface && $product->getBrand() !== null) {
                $data->setBrand((string) $product->getBrand());
            }

            if ($variant instanceof GtinAwareInterface && $variant->getGtin() !== null) {
                $data->setGtin((string) $variant->getGtin());
            } elseif ($product instanceof GtinAwareInterface && $product->getGtin() !== null) {
                $data->setGtin((string) $product->getGtin());
            }

            if ($variant instanceof MpnAwareInterface && $variant->getMpn() !== null) {
                $data->setMpn((string) $variant->getMpn());
            } elseif ($product instanceof MpnAwareInterface && $product->getMpn() !== null) {
                $data->setMpn((string) $product->getMpn());
            }

            //modif Yannick
            if($product->getBrand())
            {
                $data->setBrand((string) $product->getBrand());
            }
            if($product->hasAttributeByCodeAndLocale('code_ean'))
            {
                $ean = $product->getAttributeByCodeAndLocale('code_ean');
                $data->setGtin((string)$ean->getValue());
            }
            $data->setMpn($variant->getCode());
            //fin modif Yannick
            
            if ($variant instanceof SizeAwareInterface && $variant->getSize() !== null) {
                $data->setSize((string) $variant->getSize());
            } elseif ($product instanceof SizeAwareInterface && $product->getSize() !== null) {
                $data->setSize((string) $product->getSize());
            }

            if ($variant instanceof ColorAwareInterface && $variant->getColor() !== null) {
                $data->setColor((string) $variant->getColor());
            } elseif ($product instanceof ColorAwareInterface && $product->getColor() !== null) {
                $data->setColor((string) $product->getColor());
            }

            // ajout Yannick
            $data->setCode($variant->getCode());

            $data->setQty((string)$variant->getOnHand());

            [$univers, $subCat1, $subCat2] = $this->getUniversAndSubCats($product);
            $data->setUnivers((string) $univers);
            $data->setSubCat1((string) $subCat1);
            $data->setSubCat2((string) $subCat2);

            if($product->hasAttributeByCodeAndLocale('annee'))
            {
                $annee = $product->getAttributeByCodeAndLocale('annee');
                $data->setYear((string)$annee->getValue());
            }

            if($product->hasAttributeByCodeAndLocale('supplier_ref'))
            {
                $supplierRef = $product->getAttributeByCodeAndLocale('supplier_ref');
                $data->setSupplierRef((string)$supplierRef->getValue());
            }
            // fin ajout Yannick

            if($variant->getOnHand() > 0) 
                $contextList->add($data);
        }

        return $contextList;
    }
    private function getTranslation(TranslatableInterface $translatable, string $locale): ?TranslationInterface
    {
        /** @var TranslationInterface $translation */
        foreach ($translatable->getTranslations() as $translation) {
            if ($translation->getLocale() === $locale) {
                return $translation;
            }
        }

        return null;
    }

    private function getLink(LocaleInterface $locale, ProductTranslationInterface $translation): ?string
    {
        if ($translation->getSlug() === null || $locale->getCode() === null) {
            return null;
        }

        return $this->router->generate(
            'sylius_shop_product_show',
            ['slug' => $translation->getSlug(), '_locale' => $locale->getCode()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
    }

    private function getImageLink(ImagesAwareInterface $imagesAware): ?string
    {
        $images = $imagesAware->getImagesByType('main');
        if ($images->count() === 0) {
            $images = $imagesAware->getImages();
        }

        if ($images->count() === 0) {
            return null;
        }

        /** @var ImageInterface|false $image */
        $image = $images->first();
        if (false === $image) {
            return null;
        }

        return $this->cacheManager->getBrowserPath((string) $image->getPath(), 'sylius_shop_product_large_thumbnail');
    }

    /**
     * Index 0 equals the price
     * Index 1 equals the sale price (if set)
     * 
     * Ajout Yannick
     * Index 2 equals the tax-excl price 
     * Index 3 equals the tax percent
     */
    private function getPrices(ProductVariantInterface $variant, ChannelInterface $channel): array
    {
        $channelPricing = $variant->getChannelPricingForChannel($channel);

        if (null === $channelPricing) {
            return [null, null, null, null];
        }

        $originalPrice = $channelPricing->getOriginalPrice();
        $price = $channelPricing->getPrice();

        $taxAmount = 0;
        if($tax = $variant->getTaxCategory())
        {
            $rate = $tax->getRates()->first();
            $taxAmount = $rate->getAmount();
        }

        if (null === $price) {
            return [null, null, null, null];
        }

        // modif Yannick
        $_price = $this->createPrice($price, $channel);
        if (null === $originalPrice) {
            $taxExclPrice = $price / 100 / ($taxAmount + 1);
            return [$_price, $_price, $taxExclPrice, $taxAmount];
        }

        // modif Yannick
        $_originalPrice = $this->createPrice($originalPrice, $channel);
        $taxExclPrice = $price / 100 / ($taxAmount + 1);
        return [$_originalPrice, $_price, $taxExclPrice, $taxAmount];
    }

    private function getAvailability(ProductVariantInterface $product): Availability
    {
        return $this->availabilityChecker->isStockAvailable($product) ? Availability::inStock() : Availability::outOfStock();
    }

    private function createPrice(int $price, ChannelInterface $channel): ?Price
    {
        $baseCurrency = $channel->getBaseCurrency();
        if (null === $baseCurrency) {
            return null;
        }

        return new Price($price, $baseCurrency);
    }

    private function getProductType(ProductInterface $product, LocaleInterface $locale, bool $excludeRoot = false): ?string
    {
        if ($product->getMainTaxon() !== null) {
            $taxon = $product->getMainTaxon();
        } elseif (count($product->getTaxons()) > 0) {
            /** @var TaxonInterface $taxon */
            $taxon = $product->getTaxons()->first();
        } else {
            return null;
        }

        $breadcrumbs = [];
        array_unshift($breadcrumbs, $taxon);
        for ($breadcrumb = $taxon->getParent(); null !== $breadcrumb; $breadcrumb = $breadcrumb->getParent()) {
            array_unshift($breadcrumbs, $breadcrumb);
        }

        if ($excludeRoot) {
            // In cases when some root taxon assigned to channel's menuTaxon,
            // we don't want to display root taxon - remove first item
            array_shift($breadcrumbs);
        }

        return implode(' > ', array_map(function (TaxonInterface $breadcrumb) use ($locale): string {
            /** @var TaxonTranslationInterface|null $translation */
            $translation = $this->getTranslation($breadcrumb, (string) $locale->getCode());

            // Fallback to default locale
            return null !== $translation ? (string) $translation->getName() : (string) $breadcrumb->getName();
        }, $breadcrumbs));
    }

    private function getUniversAndSubCats($product)
    {
        $univers = $subCat1 = $subCat2 = '';
        $productTaxons = $product->getProductTaxons();
        foreach($productTaxons as $productTaxon)
        {
            if($taxon = $productTaxon->getTaxon())
            {
                if($taxon->getLevel() == 1) $univers = $taxon->getName();
                if($taxon->getLevel() == 2) $subCat1 = $taxon->getName();
                if($taxon->getLevel() == 3) $subCat2 = $taxon->getName();
            }
        }

        return [$univers, $subCat1, $subCat2];
    }

    private function getTrekmagType($product)
    {
        $types = array(
            '130'   => 'wear-tech',
            '274'   => 'wear-tech',
            '264'   => 'ski',
            '265'   => 'ski',
            '299'   => 'snowboard',
            '300'   => 'snowboard',
            '304'   => 'fix-snow',
            '277'   => 'chaussure-ski',
            '303'   => 'boots-snow',
            '584'   => 'boots-snow',
            '583'   => 'ski-rando',
            '285'   => 'ski-rando',
            '287'   => 'fix-ski-rando',
            '286'   => 'chaussure-ski-rando',
            '292'   => 'peau-ski-rando',
            '602'   => 'couteau-ski-rando',
            '319'   => 'casque',
            '15'    => 'casque',
            '33'    => 'casque',
            '484'   => 'casque',
            '485'   => 'casque',
            '533'   => 'baton',
            '268'   => 'baton',
            '291'   => 'baton',
            '726'   => 'baton',
            '59'    => 'piolet',
            '57'    => 'crampon',
            '10'    => 'harnais',
            '51'    => 'harnais',
            '53'    => 'harnais',
            '80'    => 'harnais',
            '81'    => 'harnais',
            '82'    => 'harnais',
            '34'    => 'harnais',
            '26'    => 'harnais',
            '42'    => 'harnais',
            '329'   => 'dva',
            '330'   => 'pelle',
            '331'   => 'sonde',
            '327'   => 'airbag',
            '105'   => 'equipement-outdoor',
            '491'   => 'equipement-outdoor',
            '110'   => 'equipement-outdoor',
            '111'   => 'equipement-outdoor',
            '492'   => 'equipement-outdoor',
            '493'   => 'equipement-outdoor',
            '494'   => 'equipement-outdoor',
            '128'   => 'equipement-outdoor',
            '106'   => 'equipement-outdoor',
            '107'   => 'equipement-outdoor',
            '108'   => 'equipement-outdoor',
            '109'   => 'equipement-outdoor',
            '113'   => 'equipement-outdoor',
            '114'   => 'equipement-outdoor',
            '603'   => 'equipement-outdoor',
            '115'   => 'equipement-outdoor',
            '116'   => 'equipement-outdoor',
            '435'   => 'equipement-outdoor',
            '121'   => 'equipement-outdoor',
            '123'   => 'equipement-outdoor',
            '124'   => 'equipement-outdoor',
            '122'   => 'equipement-outdoor',
            '125'   => 'equipement-outdoor',
            '496'   => 'equipement-outdoor',
            '133'   => 'equipement-outdoor',
            '132'   => 'equipement-outdoor',
            '469'   => 'equipement-outdoor',
            '193'   => 'equipement-outdoor',
            '194'   => 'equipement-outdoor',
            '196'   => 'equipement-outdoor',
            '522'   => 'equipement-outdoor',
            '523'   => 'equipement-outdoor',
            '169'   => 'equipement-outdoor',
            '165'   => 'equipement-outdoor',
            '166'   => 'equipement-outdoor',
            '167'   => 'equipement-outdoor',
            '500'   => 'equipement-outdoor',
            '504'   => 'equipement-outdoor',
            '505'   => 'equipement-outdoor',
            '649'   => 'equipement-outdoor',
            '650'   => 'equipement-outdoor',
            '651'   => 'equipement-outdoor',
            '171'   => 'equipement-outdoor',
            '172'   => 'equipement-outdoor',
            '177'   => 'equipement-outdoor',
            '174'   => 'equipement-outdoor',
            '188'   => 'equipement-outdoor',
            '205'   => 'equipement-outdoor',
            '206'   => 'equipement-outdoor',
            '207'   => 'equipement-outdoor',
            '192'   => 'sac-dos',
            '161'   => 'chaussure-rando',
            '37'    => 'chausson',
            '18'    => 'equipement-escalade',
            '27'    => 'equipement-escalade',
            '29'    => 'equipement-escalade',
            '30'    => 'equipement-escalade',
            '32'    => 'equipement-escalade',
            '35'    => 'equipement-escalade',
            '36'    => 'equipement-escalade',
            '39'    => 'equipement-escalade',
            '40'    => 'equipement-escalade',
            '43'    => 'equipement-escalade',
            '34'    => 'equipement-escalade',
            '94'    => 'equipement-escalade',
            '95'    => 'equipement-escalade',
            '96'    => 'equipement-escalade',
            '97'    => 'equipement-escalade',
            '473'   => 'equipement-escalade',
            '479'   => 'equipement-escalade',
            '481'   => 'equipement-escalade',
            '483'   => 'equipement-escalade',
            '98'    => 'equipement-escalade',
            '99'    => 'equipement-escalade',
            '100'   => 'equipement-escalade',
            '471'   => 'equipement-escalade',
            '97'    => 'equipement-escalade',
            '89'    => 'equipement-escalade',
            '86'    => 'equipement-escalade',
            '88'    => 'equipement-escalade',
            '404'   => 'equipement-escalade',
            '90'    => 'equipement-escalade',
            '92'    => 'equipement-escalade',
            '513'   => 'equipement-escalade',
            '93'    => 'equipement-escalade',
            '536'   => 'equipement-escalade',
            '41'    => 'equipement-escalade',
            '46'    => 'equipement-escalade',
            '47'    => 'equipement-escalade',
            '486'   => 'equipement-escalade',
            '487'   => 'equipement-escalade',
            '223'   => 'trail-running',
            '227'   => 'trail-running',
            '228'   => 'trail-running',
            '229'   => 'trail-running',
            '230'   => 'trail-running',
            '337'   => 'vtt',
            '353'   => 'pneu-vtt',
            '354'   => 'roue-vtt',
            '347'   => 'peripherique-vtt',
            '421'   => 'equipement-vtt',
            '320'   => 'optique',
            '718'   => 'optique',
            '752'   => 'optique'
        );

        foreach($prod->getCategoryIds() as $id)
        {
            if($types[$id])
            {
                return $types[$id];
            }
        }
    }
}
