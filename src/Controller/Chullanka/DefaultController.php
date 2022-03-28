<?php

declare(strict_types=1);

namespace App\Controller\Chullanka;

use App\Entity\Chullanka\HistoricOrder;
use App\Entity\Chullanka\Parameter;
use App\Entity\Chullanka\Rma;
use App\Entity\Chullanka\RmaProduct;
use App\Entity\Chullanka\StoreService;
use App\Entity\Customer\Customer;
use App\Entity\Order\Order;
use App\Entity\Product\Product;
use App\Entity\Product\ProductAttribute;
use App\Entity\Product\ProductAttributeValue;
use App\Entity\Product\ProductOption;
use App\Entity\Shipping\ShippingMethod;
use App\Entity\Taxation\TaxCategory;
use App\Entity\Taxonomy\Taxon;
use App\Form\Type\FavoriteSportType;
use App\Form\Type\FavoriteStoreType;
use App\Form\Type\RmaType;
use App\Service\GinkoiaCustomerWs;
use App\Service\GinkoiaHelper;
use App\Service\UpstreamPayWidget;
use BitBag\SyliusCmsPlugin\Entity\Block;
use BitBag\SyliusCmsPlugin\Entity\Page;
use BitBag\SyliusCmsPlugin\Entity\Section;
use SM\Factory\FactoryInterface;
use Sylius\Component\Core\OrderCheckoutTransitions;
use Sylius\Component\Core\OrderPaymentStates;
use Sylius\Component\Core\OrderPaymentTransitions;
use Sylius\Component\Order\Context\CartContextInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment;

final class DefaultController extends AbstractController
{
    /** @var CartContextInterface */
    private $cartContext;

    /** @var Environment */
    private $twig;

    /** @var GinkoiaCustomerWs */
    private $ginkoiaCustomerWs;

    private EventDispatcherInterface $eventDispatcher;

    public function __construct(CartContextInterface $cartContext, Environment $twig, GinkoiaCustomerWs $ginkoiaCustomerWs, EventDispatcherInterface $eventDispatcher)
    {
        $this->cartContext = $cartContext;
        $this->twig = $twig;
        $this->ginkoiaCustomerWs = $ginkoiaCustomerWs;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @Route("/", name="chullanka_index")
     */
    public function indexAction(Request $request)
    {
        error_log("DefaultController index");
        return $this->redirectToRoute('sylius_shop_homepage');
    }

    /**
     * @Route("/test", name="default_test")
     */
    public function testAction(FactoryInterface $stateMachineFactory, GinkoiaHelper $ginkoiaHelper)
    {

        $order = $this->container->get('doctrine')->getRepository(Order::class)->find(46);

        echo $order->isPanierMixte() ? "panier mixte" :" ok";
        die;

        $noShip = $noShop = false;
		$inShop = [];
        foreach($order->getItems() as $item)
        {
            $askQty = $item->getQuantity();
            $variant = $item->getVariant();

            if(!$variant->getOnHand() < $askQty) $noShip = true;
            foreach($variant->getStocks() as $stock)
            {
                $inShop[ $variant->getId() ][ $stock->getStore()->getId() ] = (bool)$stock->getOnHand();
            }
        }
        
        // test tous les produits par magasin
		$noShop = true;
		foreach($inShop as $p => $dispo)
		{
			if(!isset($dispoShops)) $dispoShops = $dispo;
			$dispoShops = array_intersect_assoc($dispoShops, $dispo);
		}

        foreach($dispoShops as $s => $dispo)
		{
			if($dispo) $noShop = false;
		}
		
		// si c'est indispo en livraison et en magasin pour certains produits = panier mixte
		if((count($order->getItems()) > 1) && $noShip && $noShop) 
        {
            echo "<p>panier mixte !</>";
        }
        echo "tout va bien";

        die;


        $taxCats = $this->container->get('doctrine')->getRepository(TaxCategory::class)->findAll();
        dd($taxCats);
        die;

        $_options = [];
        $options = $this->container->get('doctrine')->getRepository(ProductOption::class)->findAll();
        foreach($options as $opt)
        {
            $_options[ $opt->getId() ] = $opt;
        }
        dd($_options);
        die;

        /*
        $_attributes = [];
        $attributes = $this->container->get('doctrine')->getRepository(ProductAttribute::class)->findAll();
        foreach($attributes as $attr)
        {
            $_attributes[ $attr->getCode() ] = $attr;
        }

        $attribute = $_attributes['couleurs'];
        
        $_val = 'Vert';

        $attrValue = new ProductAttributeValue();
        $attrValue->setAttribute($attribute);
        switch($attribute->getType())
        {
            case 'select':
                $conf = $attribute->getConfiguration();
                $choices = $conf['choices'];
                foreach($choices as $key => $choice)
                {
                    if($choice['fr_FR'] == $_val)
                    {
                        $attrValue->setValue([$key]);
                        break;
                    }
                }
                break;
            
            case 'checkbox':
                $attrValue->setValue( (bool)$_val );
                break;
            case 'text':
            default:
                $attrValue->setValue($_val);
        }

        $product = $this->container->get('doctrine')->getRepository(Product::class)->findOneBy(['code' => '0-34951']);
        $product->addAttribute($attrValue);

        $em = $this->container->get('doctrine')->getManager();
        $em->flush();*/

        die;

        //$stateMachineFactory = $this->container->get('sm.factory');
        //dd($stateMachineFactory);

        $order = $this->container->get('doctrine')->getRepository(Order::class)->find(37);
        //dd($order);

        

        $stateMachine = $stateMachineFactory->get($order, OrderCheckoutTransitions::GRAPH);



        //$em->persist($order);
        
        //cf. https://docs.sylius.com/en/latest/book/orders/orders.html#how-to-add-a-payment-to-an-order
        $stateMachineBis = $stateMachineFactory->get($order, OrderPaymentTransitions::GRAPH);
        dd($stateMachineBis->getPossibleTransitions());

        echo "oui !";

        die;

        //$order = $this->container->get('doctrine')->getRepository(Order::class)->find(10);
        //echo $ginkoiaHelper->export($order);

        if($customer = $this->getCurrentCustomer())
        {

            /* $items = [
                [
                    'name' => 'Snowboard',
                    'reference' => '4569754855',
                    'price' => 20000,
                    'quantity' => 1
                ],
                [
                    'name' => 'Chapka',
                    'reference' => '8ysu8id',
                    'price' => 6099,
                    'quantity' => 2
                ]
            ];

            $historic = new HistoricOrder();
            $historic   ->setCustomer($customer)
                        ->setOrigin('approach')
                        ->setOrderId('5596865')
                        ->setSku('Fhsdfjh45FQ')
                        ->setOrderDate(new \Datetime('2022-01-22'))
                        ->setItems($items)
                        ->setAddress('17 rue du pommier fleuri, 44300 La Chapelle de là, FR')
                        ->setShipment('Colissimo')
                        ->setShipmentPrice(600)
                        ->setShipmentDate(new \Datetime('2022-01-23'))
                        ->setTotal(26699)
                        ->setPaymethod('Carte bleue')
                        ->setInvoice('#FACT0416504')
            ; */
            /*
            $items = [
                [
                    'name' => 'FENIX 6X SOLAR CARBON GRAY DLC BRAC NOIR - GARMIN FRANCE SAS',
                    'reference' => '2000000010991',
                    'code_chono' => '0-1974',
                    'price' => 84999,
                    'quantity' => 1
                ]
            ];

            $historic = new HistoricOrder();
            $historic   ->setCustomer($customer)
                        ->setOrigin('magasin')
                        ->setOrderId('50393265')
                        ->setSku('TI-2-9VF-14-64-1')
                        ->setOrderDate(new \Datetime('2021-06-01'))
                        ->setItems($items)
                        ->setAddress('Antibes')
                        ->setShipment('CHULLANKA ANTIBES')
                        ->setShipmentPrice(0)
                        ->setShipmentDate(new \Datetime('2021-06-01'))
                        ->setTotal(84999)
                        ->setPaymethod('CARTES BLEUES')
                        ->setInvoice('')
            ;

            $em = $this->container->get('doctrine')->getManager();
            $em->persist($historic);
            $em->flush();

            echo "C ajouté !";*/
        }
        die;
    }

    /**
     * @Route("/getunivers", name="get_first_level_taxon")
     */
    public function getUniversAction(Request $request)
    {
        $univers = $this->container->get('doctrine')->getRepository(Taxon::class)->findBy(['level' => 1], ['position' => 'ASC']);
        return $this->render('@SyliusShop/Homepage/_univers_list.html.twig', [
            'univers' => $univers
        ]);
    }

    /**
     * @Route("/homestoreservices", name="chk_home_store_services")
     */
    public function getHomeStoreServices(Request $request)
    {
        $services = $this->container->get('doctrine')->getRepository(StoreService::class)->findBy([
            'enabled' => true,
            'show_home' => true
        ]);
        return $this->render('@SyliusShop/Homepage/_shopNewsContent.html.twig', [
            'services' => $services
        ]);
    }

    /**
     * @Route("/productbox/{id}", name="chk_partial_product_box")
     */
    public function getProductBox(Request $request)
    {
        $id = $request->get('id');
        $product = $this->container->get('doctrine')->getRepository(Product::class)->find($id);
        return $this->render('@SyliusShop/Product/_box.html.twig', [
            'product' => $product
        ]);
    }

    /**
     * @Route("/lastblogposts", name="chk_last_blog_posts")
     */
    public function getBlogFeedAction(Request $request)
    {
        $blogfeedurl = $this->chkParameter('blogfeedurl');

        if($univers = $request->get('univers'))
        {
            $taxon = $this->container->get('doctrine')->getRepository(Taxon::class)->find($univers);
            if(($catfeedurl = $taxon->getBlogfeedurl()) && !empty($catfeedurl))
            {
                $blogfeedurl = $catfeedurl;
            }
        }
        
        $rss = simplexml_load_file($blogfeedurl);

        $limit = 10;
        $blogposts = [];
        for ($i=0; $i<$limit; $i++)
        {
            $item = $rss->channel->item[ $i ];
            $date =  date_create_from_format(\DateTime::RSS, (string)$item->pubDate);
            $item->dateTxt = $date->format('d/m/Y');
            $blogposts[] = $item;
        }

        return $this->render('@SyliusShop/Layout/_lastnews.html.twig', [
            'blogposts' => $blogposts
        ]);
    }


    /**
     * @Route("/pagesbysection", name="get_pages_by_section")
     */
    public function getPagesBySection(Request $request)
    {
        $template = $request->get('template') ?? '@SyliusShop/Layout/_footer_links.html.twig';
        $sectionCode = $request->get('sectionCode');
        
        $pageRepo = $this->container->get('doctrine')->getRepository(Page::class);
        $pages = $pageRepo->createQueryBuilder('o')
            ->innerJoin('o.sections', 'section')
            ->where('o.enabled = true')
            ->andWhere('section.code = :sectionCode')
            ->setParameter('sectionCode', $sectionCode)
            ->getQuery()
            //->getOneOrNullResult()
            ->getResult()
        ;
        return $this->render($template, [
            'pages' => $pages
        ]);
    }

    /**
     * @Route("/blocksbysectiontaxon", name="get_blocks_by_section_taxon")
     */
    public function getBlocksBySectionAndTaxonAction(Request $request)
    {
        $template = $request->get('template') ?? '@SyliusShop/Block/simple_blocklist.html.twig';
        $sectionCode = $request->get('sectionCode');
        $showCallback = $request->get('showCallback');
        $taxonCode = $request->get('taxonCode');

        $blockRepo = $this->container->get('doctrine')->getRepository(Block::class);
        do 
        {
            $blocks = $blockRepo->createQueryBuilder('o')
                                ->innerJoin('o.taxonomies', 'taxon')
                                ->innerJoin('o.sections', 'section')
                                ->where('o.enabled = true')
                                ->andWhere('section.code = :sectionCode')
                                ->andWhere('taxon.code = :taxonCode')
                                ->setParameter('sectionCode', $sectionCode)
                                ->setParameter('taxonCode', $taxonCode)
                                ->getQuery()
                                //->getOneOrNullResult()
                                ->getResult()
            ;

            $parent = false;
            if(count($blocks) <= 0)
            {
                $taxon = $this->container->get('doctrine')->getRepository(Taxon::class)->findOneByCode($taxonCode);
                if($taxon->getLevel() > 1)
                {
                    $parent = $taxon->getParent();
                    $taxonCode = $parent->getCode();
                }
            }
        }
        while((count($blocks) <= 0) && ($parent != false));

        return $this->render($template, [
            'blocks' => $blocks,
            'showCallback' => $showCallback
        ]);
    }

    /**
     * @Route("/freedeliveryblock", name="chk_free_delivery_block")
     */
    public function getFreeDeliveryBlockAction(Request $request)
    {
        $amount = 0;
        $canBeFree = true;
        $itsFree = false;
        $repository = $this->container->get('doctrine')->getRepository(ShippingMethod::class);
        if($shippingMethod = $repository->findOneByCode('home_standart'))
        {
            $configuration = $shippingMethod->getConfiguration();
            $freeAbove = $configuration['free_above'];
            
        
            $order = $order = $this->cartContext->getCart();;
            if($shipAddress = $order->getShippingAddress())
            {
                $countryCode = $shipAddress->getCountryCode();
                if($countryCode == 'FR')
                {
                    $postcode = $shipAddress->getPostcode();
                    $department = substr($postcode, 0, 2);
                    if(in_array($department, ['20','97','98'])) $canBeFree = false;
                }
                else $canBeFree = false;
            }
            if($canBeFree && $freeAbove)
            {
                $totalCart = $order->getItemsTotal();
                if($totalCart >= $freeAbove) $itsFree = true;
                else  $amount = $freeAbove - $totalCart;
            }
        }
        return $this->render('@SyliusShop/Cart/Summary/_free_delivery_block.html.twig', [
            'canBeFree' => $canBeFree,
            'itsFree' => $itsFree,
            'amount' => $amount,
        ]);
    }

    /**
     * @Route("/upstreampaywidget", name="chk_upstream_payment_widget")
     */
    public function UpstreamPayWidgetAction(Request $request, UpstreamPayWidget $upstreamPayWidget)
    {
        $cart = $this->cartContext->getCart();
        $cart->setCustomerIp($request->getClientIp());

        $upStreamSession = '{}';
        if(($userCustomer = $this->getCurrentCustomer()) && $userCustomer->hasOrder($cart))
        {
            $upStreamSession = $upstreamPayWidget->getUpStreamPaySession($cart);
            $further = $cart->getFurther();
            $further['upstreampay_session_id'] = $upstreamPayWidget->getSessionId();
            $cart->setFurther($further);
        }
        error_log("session : ".$upStreamSession);

        // Save cart
        $em = $this->container->get('doctrine')->getManager();
        $em->persist($cart);
        $em->flush();

        return $this->render('chullanka/upstreampay_widget.html.twig', [
            'payment_base_url' => $upstreamPayWidget->upstreampay_base_url,
            'entity_id' => $upstreamPayWidget->entity_id,
            'api_key' => $upstreamPayWidget->api_key,
            'chk_upstreampay' => $upStreamSession,
        ]);
    }

    /**
     * @Route("/upstreampayreturn", name="chk_upstream_payment_return")
     */
    public function UpstreamPaymentAction(Request $request, UpstreamPayWidget $upstreamPayWidget, FactoryInterface $stateMachineFactory)
    {
        if($request->get('hook'))
        {
            echo "HOOK !";
        }

        if($request->get('success'))
        {
            $order = $this->cartContext->getCart();
            
            error_log('SUCCESS !');
            if($infos = $upstreamPayWidget->getSessionInfos())
            {
                error_log(print_r($infos, true));
                $return = $infos[0];

                /*
                stdClass Object
                (
                    [id] => b9dddf66-fa2a-44e4-a718-2bda34d2b90f
                    [session_id] => dd28a0d9-c68a-43a6-8699-a7bae0d69ae3
                    [partner] => braintree
                    [method] => paypal
                    [status] => stdClass Object
                            [action] => AUTHORIZE
                            [state] => SUCCESS
                            [code] => SUCCEEDED
                    [date] => 2022-02-23T16:52:15.646668501
                    [transaction_id] => b9dddf66-fa2a-44e4-a718-2bda34d2b90f
                    [plugin_result] => stdClass Object
                            [status] => 1000
                            [amount] => 16
                            [logs] => stdClass Object
                            [cardHolder] => payer@example.com
                            [status] => authorized
                            */
                if($return->status && ($return->status->state == 'SUCCESS'))
                {
                    
                    $order->setNotes('Revenu avec succès de la plateforme de paiement : '.$return->method);

                    // changer le state
                    //cf. https://docs.sylius.com/en/latest/book/orders/checkout.html#finalizing
                    $stateMachine = $stateMachineFactory->get($order, OrderCheckoutTransitions::GRAPH);
                    //$stateMachine->apply(OrderCheckoutTransitions::TRANSITION_SELECT_PAYMENT);
                    $stateMachine->apply(OrderCheckoutTransitions::TRANSITION_COMPLETE);

                    
                    //cf. https://docs.sylius.com/en/latest/book/orders/orders.html#how-to-add-a-payment-to-an-order
                    $stateMachineBis = $stateMachineFactory->get($order, OrderPaymentTransitions::GRAPH);
                    $stateMachineBis->apply(OrderPaymentTransitions::TRANSITION_PAY);
                    
                    $payment = $order->getPayments()->first();
                    $paymentStateMachine = $stateMachineFactory->get($payment, 'sylius_payment');
                    $paymentStateMachine->apply('complete');


                    // EntityManager
                    $em = $this->container->get('doctrine')->getManager();

                    
                    // IP
                    $order->setCustomerIp($request->getClientIp());

                    //Chullpoints
                    $fidelityUsed = false;
                    foreach($order->getAdjustments() as $adjustement)
                    {
                        if($adjustement->getOriginCode() == 'chk_chullpoints')
                        {
                            $fidelityUsed = true;
                        }
                    }
                    if($fidelityUsed)
                    {
                        if($customer = $order->getCustomer())
                        {
                            $chullz = $customer->getChullpoints(); //nbr de points sur le site
                            $nbrReduc = (int)floor($chullz / 500); // 500 points = 1 bon
                            $points = ($nbrReduc * 500);// on déduit le nombre de points
                            
                            
                            //on met à jour sur le WS
                            $webserv = $this->ginkoiaCustomerWs;
                            $email = $customer->getEmail();
                            if($webserv->usePoints($points, $email))
                            {
                                // on met à jour sur le site
                                $chullz -= $points;
                                $customer->setChullpoints($chullz);
                                $em->persist($customer);
                            }
                        }
                    }

                    // hack
                    if($number = $order->getNumber())
                    {
                        $number = '1' . substr($number, 1);
                        $order->setNumber($number);
                    }
                    
                    $em->persist($order);
                    $em->flush();
                    
                    // dispatch event
                    $this->eventDispatcher->dispatch(new GenericEvent($order), 'sylius.order.post_complete');
                }
            }
            //return $this->redirectToRoute('sylius_shop_order_thank_you');
            //==> quelque chose fait que le controller nous renvoie ensuite sur la home
            return $this->render('@SyliusShop/Order/thankYou.html.twig', [
                'order' => $order
            ]);
        }

        if($request->get('failure'))
        {
            echo "FAIL";
            if($infos = $upstreamPayWidget->getSessionInfos())
            {
                dd($infos);
            }
        }
        die;
    }

    /**
     * @Route("/historicorders", name="chk_historic_orders")
     */
    public function historicOrdersAction()
    {
        if(($customer = $this->getCurrentCustomer()) && $customer->getHistoricOrders()->count())
        {
            $magasinOrders = $this->container->get('doctrine')->getRepository(HistoricOrder::class)->findBy([
                'customer' => $customer,
                'origin' => 'magasin'
            ]);

            $approachOrders = $this->container->get('doctrine')->getRepository(HistoricOrder::class)->findBy([
                'customer' => $customer,
                'origin' => 'approach'
            ]);

            return $this->render('chullanka/customer/historicorder/index.html.twig', [
                'magasin_orders' => $magasinOrders,
                'approach_orders' => $approachOrders,
            ]);
        }
        return $this->redirectToRoute('sylius_shop_account_dashboard');
    }

    /**
     * @Route("/historicorder/{id}", name="chk_historic_order_view")
     */
    public function historicOrderViewAction(string $id)
    {
        if(($customer = $this->getCurrentCustomer()) && $customer->getHistoricOrders()->count())
        {
            $order = $this->container->get('doctrine')->getRepository(HistoricOrder::class)->find($id);
            if($order->getCustomer() === $customer)
            {
                $links = [];
                foreach($order->getItems() as $item)
                {
                    $pid = $item['reference'];
                    if($pid == '2000000010991')
                    {
                        $item['plink'] = '/unlienquelconque';
                    }
                }
                return $this->render('chullanka/customer/historicorder/view.html.twig', [
                    'order' => $order
                ]);
            }
        }
        return $this->redirectToRoute('chk_historic_orders');
    }


    /**
     * @Route("/rma/requestlist", name="rma_request_list")
     */
    public function rmaRequestListAction()
    {
        if(($customer = $this->getCurrentCustomer()) && $customer->getRmas()->count())
        {
            return $this->render('chullanka/rma/index.html.twig', [
                'rmas' => $customer->getRmas()
            ]);
        }
        return $this->redirectToRoute('sylius_shop_account_dashboard');
    }

    /**
     * @Route("/rma/askreturnproduct/{order_id}", name="rma_ask_return")
     */
    public function askReturnProductAction(string $order_id, Request $request)
    {
        $order = $this->container->get('doctrine')->getRepository(Order::class)->find($order_id);
        if(!is_null($order) && ($customer = $this->getCurrentCustomer()) && $customer->hasOrder($order))
        {
            // init values
            $rma = new Rma();
            $rma->setOrder($order);
            $rma->setCustomer($customer);
            $number = $order->getNumber() . '-' . ($order->getRmas()->count() + 1);
            $rma->setNumber($number);
            $rma->setCustomerEmail($customer->getEmail());
            foreach($order->getItems() as $item)
            {
                $rmaProduct = new RmaProduct();
                $rmaProduct->setOrderitem($item);
                $rmaProduct->setRma($rma);
                $rma->addRmaProduct($rmaProduct);
            }

            $form = $this->createForm(RmaType::class, $rma);
            $form->handleRequest($request);
            if($form->isSubmitted() && $form->isValid())
            {
                $em = $this->container->get('doctrine')->getManager();

                $num = 0;
                foreach($rma->getRmaProducts() as $rmaProduct)
                {
                    if($rmaProduct->getQuantity() > 0)
                    {
                        $em->persist($rmaProduct);
                        $num++;
                    }
                    else 
                    {
                        $rma->removeRmaProduct($rmaProduct);
                    }
                }

                if($num > 0)
                {
                    $rma->setState('new');
                    $em->persist($rma);
                    $em->flush();
                }
                return $this->redirectToRoute('rma_request_list');
            }

            return $this->render('chullanka/rma/ask_return_product.html.twig', [
                'customer' => $customer,
                'order' => $order,
                'form' => $form->createView()
            ]);
        }   
        return $this->redirectToRoute('sylius_shop_homepage');
    }

    /**
     * @Route("/rma/request/{id}", name="rma_request_view")
     */
    public function rmaRequestViewAction(string $id)
    {
        if(($customer = $this->getCurrentCustomer()) && $customer->getRmas()->count())
        {
            $rma = $this->container->get('doctrine')->getRepository(Rma::class)->find($id);
            if($rma->getCustomer() === $customer)
            {
                return $this->render('chullanka/rma/view.html.twig', [
                    'rma' => $rma
                ]);
            }
        }
        return $this->redirectToRoute('rma_request_list');
    }


    /**
     * @Route("/managefavstores", name="store_manage_favorites")
     */
    public function manageFavoriteStoresAction(Request $request)
    {
        if($customer = $this->getCurrentCustomer())
        {
            $form = $this->createForm(FavoriteStoreType::class, $customer);
            $form->handleRequest($request);
            if($form->isSubmitted() && $form->isValid())
            {
                $em = $this->container->get('doctrine')->getManager();
                $em->persist($customer);
                $em->flush();
    
                return $this->redirectToRoute('sylius_shop_account_dashboard');
            }
    
            return $this->render('chullanka/customer/manage_favorite_stores.html.twig', [
                'customer' => $customer,
                'form' => $form->createView()
            ]);
        }
        return $this->redirectToRoute('sylius_shop_homepage');
    }

    /**
     * @Route("/managefavsports", name="sport_manage_favorites")
     */
    public function manageFavoriteSportsAction(Request $request)
    {
        if($customer = $this->getCurrentCustomer())
        {
            $form = $this->createForm(FavoriteSportType::class, $customer);
            $form->handleRequest($request);
            if($form->isSubmitted() && $form->isValid())
            {
                $em = $this->container->get('doctrine')->getManager();
                $em->persist($customer);
                $em->flush();

                return $this->redirectToRoute('sylius_shop_account_dashboard');
            }

            return $this->render('chullanka/customer/manage_favorite_sports.html.twig', [
                'customer' => $customer,
                'form' => $form->createView()
            ]);
        }
        return $this->redirectToRoute('sylius_shop_homepage');
    }


    /**
     * Get current connected Customer
     */
    private function getCurrentCustomer()
    {
        return (($user = $this->getUser()) && ($customer = $user->getCustomer())) ? $customer : null;
    }

    /**
     * Return a parameter's value
     */
    private function chkParameter($slug)
    {
        return $this->container->get('doctrine')->getRepository(Parameter::class)->getValue($slug);
    }
}
