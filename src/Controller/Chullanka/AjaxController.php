<?php

namespace App\Controller\Chullanka;

use App\Entity\Chullanka\Recall;
use App\Form\Type\RecallFrontType;
use App\Service\DpdHelper;
use App\Service\GinkoiaCustomerWs;
use Doctrine\ORM\EntityManagerInterface;
use Sylius\Component\Core\Model\AdjustmentInterface;
use Sylius\Component\Core\Repository\ProductRepositoryInterface;
use Sylius\Component\Core\Repository\ProductVariantRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Sylius\Component\Order\Context\CartContextInterface;
use Sylius\Component\Order\Factory\AdjustmentFactoryInterface;
use Sylius\Component\Order\Modifier\OrderItemQuantityModifierInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

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
    private $entityManager;

    /** @var GinkoiaCustomerWs */
    private $ginkoiaCustomerWs;

    public function __construct(
        ProductRepositoryInterface $productRepository, 
        ProductVariantRepositoryInterface $productVariantRepository, 
        FactoryInterface $orderItemFactory, 
        OrderItemQuantityModifierInterface $orderItemQuantityModifier,
        CartContextInterface $cartContext,
        EntityManagerInterface $entityManager,
        GinkoiaCustomerWs $ginkoiaCustomerWs
    )
    {
        $this->productRepository = $productRepository;
        $this->productVariantRepository = $productVariantRepository;
        $this->orderItemFactory = $orderItemFactory;
        $this->orderItemQuantityModifier = $orderItemQuantityModifier;
        $this->cartContext = $cartContext;
        $this->entityManager = $entityManager;
        $this->ginkoiaCustomerWs = $ginkoiaCustomerWs;
    }

    /**
     * @Route("/", name="chk_ajax")
     */
    public function index(): Response
    {
        return new Response('AJAX', 200, ['Content-Type' => 'text/html']);
    }

    /**
     * @Route("/getadvice", name="chk_ajax_getadviceform")
     */
    public function getAdviceFormAction(Request $request): Response
    {
        $template = $request->get('template') ?? '@SyliusShop/_getAdvice.html.twig';
        $recall = new Recall();
        $recall->setState(0);
        
        if($customer = $this->getCurrentCustomer())
        {
            $recall->setCustomer($customer);
        }
        if(($pid = $request->get('pid')) && ($product = $this->productRepository->find($pid)))
        {
            $recall->setProduct($product);
        }
        
        //$form = $this->get('form.factory')->create('app_recall');
        $form = $this->createForm(RecallFrontType::class, $recall);
        $form->handleRequest($request);
        
        $allIsGood = false;
        if($form->isSubmitted() && $form->isValid())
        {
            $em = $this->container->get('doctrine')->getManager();
            $em->persist($recall);
            $em->flush();
            $allIsGood = true;
        }
        return $this->render($template, [
            'allIsGood' => $allIsGood,
            'recall' => $recall,
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/prodstocks/{id}", name="chk_ajax_prodstocks")
     */
    public function prodstocksAction(Request $request): JsonResponse
    {
        $data = [];
        if(($pid = $request->get('id')) && ($product = $this->productRepository->find($pid)))
        {
            $variants = $product->getVariants();
            foreach($variants as $variant)
            {
                $quantities = [];
                foreach($variant->getStocks() as $stock)
                {
                    $quantities['store' . $stock->getStore()->getId()] = $stock->getOnHand();
                }
                $quantities['web'] = $variant->getOnHand();
                $data[ $variant->getId() ] = $quantities;
            }
        }
        return new JsonResponse($data);
    }

    /**
     * @Route("/addpacktocart", methods={"POST"}, name="chk_ajax_addpacktocart")
     */
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
                $further = ['pack' => []];
                foreach($sylius_add_to_cart['packItem'] as $vid)
                {
                    /** @var ProductVariantInterface $variant */
                    $variant = $this->productVariantRepository->find($vid);
                    $variantPrice = $variant->getChannelPricingForChannel($channel)->getPrice();
                    $price += $variantPrice;
                    
                    $further['pack'][ $vid ] = $variantPrice;
                }
                $orderItem->setUnitPrice($price);


                // montage des fixations
                if(isset($sylius_add_to_cart['mounting']) && ($sylius_add_to_cart['mounting'] != false) && isset($sylius_add_to_cart['mount']))
                {
                    //$further['mount'] = $sylius_add_to_cart['mount'];
                    $further['mount'] = [];
                    foreach($sylius_add_to_cart['mount'] as $key => $val)
                    {
                        if(!empty($val)) $further['mount'][ $key ] = $val;
                    }
                }
                $orderItem->setFurther($further);

                $this->orderItemQuantityModifier->modify($orderItem, 1);
                $cart->addItem($orderItem);

                $this->entityManager->persist($cart);
                $this->entityManager->flush();
            }
        }

        return $this->redirectToRoute('sylius_shop_cart_summary');
    }

    /**
     * @Route("/showpackitem/{variantId}/{inadmin}", name="chk_ajax_showpackitem")
     */
    public function showPackItemAction(int $variantId, int $inadmin = 0): Response
    {
        $variant = $this->productVariantRepository->find($variantId);
        return $this->render('@SyliusShop/Product/_packitem.html.twig', [
            'variant' => $variant,
            'inadmin' => (bool)$inadmin,
        ]);
    }

    /**
     * @Route("/usechullpoints", name="chk_ajax_chullpoints")
     */
    public function useChullpointsAction(AdjustmentFactoryInterface $adjustmentFactory): Response
    {
        $codeName = 'chk_chullpoints';
        $order = $this->cartContext->getCart();

        $fidelityUsed = false;
        foreach($order->getAdjustments() as $adjustement)
        {
            if($adjustement->getOriginCode() == $codeName)
            {
                $order->removeAdjustment($adjustement);
                $fidelityUsed = true;
            }
        }
        if(!$fidelityUsed)
        {
            $chullz = 0; //nbr de points
            if($customer = $order->getCustomer())
            {
                $chullz = $customer->getChullpoints(); //nbr de points sur le site
                
                $email = $customer->getEmail();
                $webserv = $this->ginkoiaCustomerWs;
                /*if(($loyalties = $webserv->getCustomerLoyalties($email)) && isset($loyalties['loyalty_total_points']))
                {
                    $chullz = $loyalties['loyalty_total_points'];// on récupère le nbre de point à jour sur le WS
                    // on met à jour sur le site
                    $customer->setChullpoints($chullz);
                    $this->entityManager->persist($customer);
                }*/
            }
            $nbrReduc = (int)floor($chullz / 500); // 500 points = 1 bon
		    $discountAmount = $nbrReduc * 10; // 1 bon = 10€
            $amount = -100 * (int) $discountAmount;
            $adjustment = $adjustmentFactory->createWithData(
                AdjustmentInterface::ORDER_PROMOTION_ADJUSTMENT,
                $codeName,
                $amount
            );
            $adjustment->setOriginCode($codeName);
            $order->addAdjustment($adjustment);
        }
        
        $this->entityManager->persist($order);
        $this->entityManager->flush();

        return $this->redirectToRoute('sylius_shop_cart_summary');
    }

    /**
     * @Route("/getpickuppoints", name="chk_ajax_getpickuppoints")
     */
    public function getPickupPointsAction(Request $request, DpdHelper $dpdHelper)
    {
        $data = [];
        if($address = $request->get('address'))
        {
            $data['address'] = $address;
        }
        if($zip = $request->get('zip'))
        {
            $data['zip'] = $zip;
        }
        if($city = $request->get('city'))
        {
            $data['city'] = $city;
        }

        $dpdHelper->getPickupPoints($data);
    }


    /**
     * Get current connected Customer
     */
    private function getCurrentCustomer()
    {
        return (($user = $this->getUser()) && ($customer = $user->getCustomer())) ? $customer : null;
    }
}
