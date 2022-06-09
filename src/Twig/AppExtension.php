<?php

namespace App\Twig;

use App\Entity\Product\Product;
use App\Entity\Product\ProductVariant;
use App\Entity\Promotion\PromotionAction;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension
{
    private $translator;
    private $managerRegistry;

    public function __construct(TranslatorInterface $translator, ManagerRegistry $managerRegistry)
    {
        $this->translator = $translator;
        $this->managerRegistry = $managerRegistry;
    }

    public function getFilters()
    {
        return [
            new TwigFilter('excerpt', [$this, 'getExcerpt'], ['is_safe' => ['html']]),
            new TwigFilter('getGift', [$this, 'getGift'], ['is_safe' => ['html']]),
            new TwigFilter('maxPackItem', [$this, 'maxPackItem'], ['is_safe' => ['html']]),
        ];
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('pr', [$this, 'specialprintr']),
            new TwigFunction('t2sHash', [$this, 'hashEmail']),
            new TwigFunction('iniGet', [$this, 'iniGet']),
        ];
    }

    public function specialprintr($array)
    {
        echo "<pre>";
        print_r($array);
        echo "</pre>";
    }

    public function getExcerpt($text, $words = 100, $link = null)
    {
        $excerpt = explode(' ', $text, $words);
        if(count($excerpt) >= $words) 
        {
            array_pop($excerpt);
            $excerpt = implode(' ', $excerpt) . '...';
        }
        else
        {
            $excerpt = implode(' ', $excerpt);
        }	
        $excerpt = preg_replace('`\[[^\]]*\]`', '', $excerpt);

        if(!is_null($link))
        {
            $excerpt .= " <a href=\"$link\">" .  $this->translator->trans('app.front.readmore') . "</a>.";
        }

        return "<p>$excerpt</p>";
    }

    public function getGift($product)
    {
        if($promoActions = $this->managerRegistry->getRepository(PromotionAction::class)->findByType('gift_product_discount'))
        {
            $productCode = $product->getCode();

            //get all product Taxons
            $productTaxons = [];
            foreach($product->getProductTaxons() as $prodTaxon)
            {
                $productTaxons[] = $prodTaxon->getTaxon()->getCode();
            }

            foreach($promoActions as $action)
            {
                $promo = $action->getPromotion();
                $valid = false;
                foreach($promo->getRules() as $rule)
                {
                    $confRule = $rule->getConfiguration();
                    switch($rule->getType())
                    {
                        case 'contains_product':
                            if($confRule['product_code'] == $productCode) $valid = true;
                            break;

                        case 'has_taxon':
                            foreach($confRule['taxons'] as $taxonCode)
                            {
                                if(in_array($taxonCode, $productTaxons)) $valid = true;
                            }
                        break;
                    }
                }
                if($valid)
                {
                    $confAct = $action->getConfiguration();
                    if(isset($confAct['product_code']))
                    {
                        if($giftProduct = $this->managerRegistry->getRepository(Product::class)->findOneByCode($confAct['product_code']))
                        {
                            return $giftProduct;
                        }
                    }
                }
            }
        }
        return false;
    }

    public function maxPackItem($variantId)
    {
        $maxQty = 0;
        if($variant = $this->managerRegistry->getRepository(ProductVariant::class)->find($variantId))
        {
            $maxQty = $variant->getOnHand();
        }
        return $maxQty;
    }

    

    /** pour Target2Sell */
    public function hashEmail($string): string
    {
        return $string ? strtoupper(hash('SHA256', $string)) : '';
    }

    public function iniGet(string $option): string|false
    {
        return ini_get($option);
    }
}