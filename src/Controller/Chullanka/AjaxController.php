<?php

namespace App\Controller\Chullanka;

use Doctrine\ORM\EntityManagerInterface;
use Sylius\Component\Core\Repository\ProductRepositoryInterface;
use Sylius\Component\Core\Repository\ProductVariantRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Sylius\Component\Order\Context\CartContextInterface;
use Sylius\Component\Order\Modifier\OrderItemQuantityModifierInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AjaxController extends AbstractController
{
    /** @var ProductRepositoryInterface */
    private $productRepository;

    /** @var ProductVariantRepositoryInterface */
    private $productVariantRepository;

    /** @var FactoryInterface */
    private $orderItemFactory;

    /** @var OrderItemQuantityModifierInterface */
    private $orderItemQuantityModifier;

    /** @var CartContextInterface */
    private $cartContext;

    /** @var EntityManagerInterface */
    private $cartManager;

    public function __construct(
        ProductRepositoryInterface $productRepository, 
        ProductVariantRepositoryInterface $productVariantRepository, 
        FactoryInterface $orderItemFactory, 
        OrderItemQuantityModifierInterface $orderItemQuantityModifier,
        CartContextInterface $cartContext,
        EntityManagerInterface $cartManager
    )
    {
        $this->productRepository = $productRepository;
        $this->productVariantRepository = $productVariantRepository;
        $this->orderItemFactory = $orderItemFactory;
        $this->orderItemQuantityModifier = $orderItemQuantityModifier;
        $this->cartContext = $cartContext;
        $this->cartManager = $cartManager;
    }

    public function index(): Response
    {
        return $this->render('chullanka/ajax/index.html.twig', [
            'controller_name' => 'AjaxController',
        ]);
    }

    public function addPackToCartAction(Request $request): Response
    {
        if($sylius_add_to_cart = $request->get('sylius_add_to_cart'))
        {
            if(isset($sylius_add_to_cart['packId']) && isset($sylius_add_to_cart['packItem']) && count($sylius_add_to_cart['packItem']))
            {
                $cart = $this->cartContext->getCart();
                $channel = $cart->getChannel();
                
                $packVariantId = $sylius_add_to_cart['packId'];
                $variantPack = $this->productVariantRepository->find($packVariantId);
                
                /** @var OrderItemInterface $orderItem */
                $orderItem = $this->orderItemFactory->createNew();
                $variantPack->setShippingRequired(false);
                $orderItem->setVariant($variantPack);
                
                $price = 0;
                $further = ['pack' => $sylius_add_to_cart['packItem']];
                foreach($sylius_add_to_cart['packItem'] as $vid)
                {
                    /** @var ProductVariantInterface $variant */
                    $variant = $this->productVariantRepository->find($vid);
                    $price += $variant->getChannelPricingForChannel($channel)->getPrice();
                }
                $orderItem->setUnitPrice($price);


                // montage des fixations
                if(isset($sylius_add_to_cart['mounting']) && isset($sylius_add_to_cart['mount']) && count($sylius_add_to_cart['mount']))
                {
                    $further['mount'] = $sylius_add_to_cart['mount'];
                }
                $orderItem->setFurther($further);

                $this->orderItemQuantityModifier->modify($orderItem, 1);
                $cart->addItem($orderItem);

                $this->cartManager->persist($cart);
                $this->cartManager->flush();
            }
        }

        return $this->redirectToRoute('sylius_shop_cart_summary');

        return $this->render('chullanka/ajax/index.html.twig', [
            'controller_name' => 'ChullAjaxController',
        ]);
    }

    public function showPackItemAction(Int $variantId): Response
    {
        $variant = $this->productVariantRepository->find($variantId);
        return $this->render('@SyliusShop/Product/_packitem.html.twig', [
            'variant' => $variant,
        ]);
    }
}
