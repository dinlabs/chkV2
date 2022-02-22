<?php

declare(strict_types=1);

namespace App\Controller\Chullanka;

use App\Entity\Chullanka\HistoricOrder;
use App\Entity\Chullanka\Rma;
use App\Entity\Chullanka\RmaProduct;
use App\Entity\Customer\Customer;
use App\Entity\Order\Order;
use App\Form\Type\FavoriteSportType;
use App\Form\Type\FavoriteStoreType;
use App\Form\Type\RmaType;
use App\Service\GinkoiaHelper;
use App\Service\UpstreamPayWidget;
use BitBag\SyliusCmsPlugin\Entity\Block;
use Sylius\Component\Order\Context\CartContextInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment;

final class DefaultController extends AbstractController
{
    /** @var CartContextInterface */
    private $cartContext;

    /** @var Environment */
    private $twig;

    public function __construct(CartContextInterface $cartContext, Environment $twig)
    {
        $this->cartContext = $cartContext;
        $this->twig = $twig;
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
    public function testAction(GinkoiaHelper $ginkoiaHelper)
    {
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
     * @Route("/blocksbysectiontaxon", name="get_blocks_by_section_taxon")
     */
    public function getBlocksBySectionAndTaxonAction(Request $request)
    {
        $template = $request->get('template') ?? '@SyliusShop/Block/simple_blocklist.html.twig';
        $sectionCode = $request->get('sectionCode');
        $taxonCode = $request->get('taxonCode');
        
        $blockRepo = $this->container->get('doctrine')->getRepository(Block::class);
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
        return $this->render($template, [
            'blocks' => $blocks
        ]);
    }

    /**
     * @Route("/upstreampaywidget", name="chk_upstream_payment_widget")
     */
    public function UpstreamPayWidgetAction(UpstreamPayWidget $upstreamPayWidget)
    {
        $cart = $this->cartContext->getCart();

        //$data = $upstreamPayWidget->getFormattedData($cart);
        //dd(json_decode($data));

        $upStreamSession = '{}';
        if(($userCustomer = $this->getCurrentCustomer()) && $userCustomer->hasOrder($cart))
        {
            $upStreamSession = $upstreamPayWidget->getUpStreamPaySession($cart);
        }
        error_log("session : ".$upStreamSession);

        return $this->render('chullanka/upstreampay_widget.html.twig', [
            'entity_id' => $upstreamPayWidget->entity_id,
            'api_key' => $upstreamPayWidget->api_key,
            'chk_upstreampay' => $upStreamSession,
        ]);
    }

    /**
     * @Route("/upstreampayreturn", name="chk_upstream_payment_return")
     */
    public function UpstreamPaymentAction(Request $request, UpstreamPayWidget $upstreamPayWidget, SessionInterface $session)
    {
        if($request->get('hook'))
        {
            echo "HOOK !";
        }

        if($request->get('success'))
        {
            //echo "SUCCESS !";
            if($infos = $upstreamPayWidget->getSessionInfos())
            {
                $return = $infos[0];
                dd($return);

                $okay = true;
                if($okay)
                {
                    //todo: complete Order!
                    /*$order = $this->cartContext->getCart();
                    $order->setState('new');
                    $order->setCheckoutState('completed');
                    $order->setPaymentState('paid');

                    $em = $this->container->get('doctrine')->getManager();
                    $em->persist($order);
                    $em->flush();*/

                }
            }
            //return $this->redirectToRoute('sylius_shop_order_thank_you');
        }

        if($request->get('failure'))
        {
            echo "FAIL";
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
}
