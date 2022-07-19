<?php

namespace App\Controller\Chullanka;

use App\Entity\Chullanka\Recall;
use App\Form\Type\RecallFrontType;
use App\Service\ChronorelaisHelper;
use App\Service\DpdHelper;
use App\Service\GinkoiaCustomerWs;
use Doctrine\ORM\EntityManagerInterface;
use Sylius\Bundle\OrderBundle\Form\Type\CartItemType;
use Sylius\Bundle\OrderBundle\Form\Type\CartType;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Core\Model\AdjustmentInterface;
use Sylius\Component\Core\Repository\ProductRepositoryInterface;
use Sylius\Component\Core\Repository\ProductVariantRepositoryInterface;
use Sylius\Component\Mailer\Sender\SenderInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Sylius\Component\Order\Context\CartContextInterface;
use Sylius\Component\Order\Factory\AdjustmentFactoryInterface;
use Sylius\Component\Order\Modifier\OrderItemQuantityModifierInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
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

    /** @var SessionInterface */
    private $session;

    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var GinkoiaCustomerWs */
    private $ginkoiaCustomerWs;

    /** @var ChannelContextInterface */
    private $channelContext;

    /** @var SenderInterface */
    private $emailSender;

    public function __construct(
        ProductRepositoryInterface $productRepository, 
        ProductVariantRepositoryInterface $productVariantRepository, 
        FactoryInterface $orderItemFactory, 
        OrderItemQuantityModifierInterface $orderItemQuantityModifier,
        CartContextInterface $cartContext,
        SessionInterface $session,
        EntityManagerInterface $entityManager,
        GinkoiaCustomerWs $ginkoiaCustomerWs,
        ChannelContextInterface $channelContext,
        SenderInterface $emailSender
    )
    {
        $this->productRepository = $productRepository;
        $this->productVariantRepository = $productVariantRepository;
        $this->orderItemFactory = $orderItemFactory;
        $this->orderItemQuantityModifier = $orderItemQuantityModifier;
        $this->cartContext = $cartContext;
        $this->session = $session;
        $this->entityManager = $entityManager;
        $this->ginkoiaCustomerWs = $ginkoiaCustomerWs;
        $this->channelContext = $channelContext;
        $this->emailSender = $emailSender;
    }

    /**
     * @Route("/", name="chk_ajax")
     */
    public function index(): Response
    {
        return new Response('AJAX', 200, ['Content-Type' => 'text/html']);
    }
    
    /**
     * @Route("/profilinfos", name="chk_ajax_profilinfos")
     */
    public function getProfilInfos(Request $request): JsonResponse
    {
        $data = ['cart_items' => 0, 'notifications' => 0]; // default

        if($cart = $this->cartContext->getCart())
        {
            $data['cart_items'] = $cart->getItems()->count();
        }
        
        if($customer = $this->getCurrentCustomer())
        {
            $data['notifications'] = $customer->getNotice();
        }
        return new JsonResponse($data);
    }

    /**
     * @Route("/hideonboard", name="chk_ajax_hideonboard")
     */
    public function hideOnBoard(): JsonResponse
    {
        if($customer = $this->getCurrentCustomer())
        {
            $customer->setConnections(1);
            $em = $this->container->get('doctrine')->getManager();
            $em->persist($customer);
            $em->flush();
        }
        return new JsonResponse(['return' => 'OK']);
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
        $title = $request->get('title') ?? 'Faites-vous conseiller';
        
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

            // send email
            $channel = $this->channelContext->getChannel();
            $recipients = [$channel->getContactEmail()];
            if(($recallCustomer = $recall->getCustomer()) && ($customerEmail = $recallCustomer->getEmail()))
            {
                $recipients[] = $customerEmail;
            }
            $this->emailSender->send('ask_recall', $recipients, ['recall' => $recall]);
        }
        return $this->render($template, [
            'title' => $title,
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

            // cart with fresh token
            $data['html_cart'] = $this->renderView('@SyliusShop/Product/Show/_inventory.html.twig', [
                'product' => $product
            ]);
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
                $productPack = $variantPack->getProduct();

                // test nbr packItem
                $setted = [];
                foreach($sylius_add_to_cart['packItem'] as $vid)
                {
                    if(!empty($vid)) $setted[] = $vid;
                }

                if($productPack->getIsPack() 
                    && $productPack->getPackElements() 
                    && $productPack->getPackElements()->count() 
                    && (count($setted) < $productPack->getPackElements()->count()))
                {
                    $this->addFlash('error', 'Veuillez sélectionner vos produits');

                    // retour à la page
                    $referer = $request->headers->get('referer');
                    return $this->redirect($referer);
                }


                
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
     * @Route("/popaddtocart", name="chk_ajax_popaddtocart")
     */
    public function popAddToCartAction(Request $request): Response
    {
        $variant_id = $request->get('variant_id');
        $variant = $this->productVariantRepository->find($variant_id);
        $error = $request->get('error');
        $cart = $this->cartContext->getCart();
        $form = $this->createForm(CartType::class, $cart);
        return $this->render('chullanka/ajax/pop_addtocart.html.twig', [
            'form' => $form->createView(),
            'cart' => $cart,
            'variant_id' => $variant_id,
            'variant' => $variant,
            'error' => $error,
        ]);
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

        $this->session->remove('usedchullpoints');
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
            $this->session->set('usedchullpoints', $amount);
        }
        
        $this->entityManager->persist($order);
        $this->entityManager->flush();

        return $this->redirectToRoute('sylius_shop_cart_summary');
    }

    /**
     * @Route("/getpickuppoints", name="chk_ajax_getpickuppoints")
     */
    public function getPickupPointsAction(Request $request, DpdHelper $dpdHelper, ChronorelaisHelper $chronoRelais): Response
    {
        $data = [];
        if($address = $request->request->get('address'))
        {
            $data['address'] = $address;
        }
        if($zip = $request->request->get('zip'))
        {
            $data['zip'] = $zip;
        }
        if($city = $request->request->get('city'))
        {
            $data['city'] = $city;
        }

        $pickups = [];
        $trouble = null;
        if($shipmethod = $request->request->get('shipmethod'))
        {
            if($shipmethod == 'pickup_standart') $pickups = $dpdHelper->getPickupPoints($data);
            elseif($shipmethod == 'pickup_express') $pickups = $chronoRelais->getPickupPoints($data);
            else $trouble = "Veuillez, au préalable, choisir le tarif souhaité.";
            
            return $this->render('@SyliusShop/Checkout/SelectShipping/_pickuplist.html.twig', [
                'pickups' => $pickups,
                'trouble' => $trouble
            ]);
        }
        return new Response('Error');
    }


    /**
     * Get current connected Customer
     */
    private function getCurrentCustomer()
    {
        return (($user = $this->getUser()) && ($customer = $user->getCustomer())) ? $customer : null;
    }
}
