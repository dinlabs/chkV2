<?php

declare(strict_types=1);

namespace App\Controller\Chullanka;

use App\Entity\Chullanka\Store;
use App\Entity\Order\Order;
use App\Entity\Product\ProductVariant;
use App\Service\GinkoiaHelper;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment;

final class StoreController extends AbstractController
{
    /** @var Environment */
    private $twig;

    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
    }

    /**
     * @Route("/", name="store_index")
     */
    public function indexAction(): Response
    {
        error_log("StoreController index");

        return new Response($this->twig->render('store/index.html.twig', [
            'controller_name' => 'StoreController',
        ]));
    }

    /**
     * @Route("/shipment_list/{id_order}", name="store_shipment_list")
     */
    public function shipmentListAction(string $id_order, ManagerRegistry $managerRegistry): Response
    {
        $order = $managerRegistry->getRepository(Order::class)->find($id_order);
        if(!$order) return null;

        $selectedStore = null;
        $further = $order->getFurther();
        if($further && isset($further['store']) && !empty($further['store']))
        {
            $selectedStore = $further['store'];
        }

        $product_stocks = []; // liste des variantes à tester
        foreach($order->getItems() as $item)
        {
            $variant = $item->getVariant();
            if($further = $item->getFurther())
            {
                if(isset($further['pack']) && !empty($further['pack']))
                {
                    // récupération des produits du pack
                    foreach($further['pack'] as $ppvid => $price)
                    {
                        $ppVariant = $managerRegistry->getRepository(ProductVariant::class)->find($ppvid);
                        $product_stocks[] = $ppVariant;
                    }
                    continue;//on ne prend pas en compte les infos du pack lui-même
                }
            }
            $product_stocks[] = $variant;
        }

        $stores = $managerRegistry->getRepository(Store::class)->findAll();
        foreach($stores as $store)
        {
            // test si ce store est déjà selectionné (suite à un retour en arrière)
            $store->selected = (!is_null($selectedStore) && ($selectedStore == $store->getId()));

            // test si les produits sont dispo dans ce magasin
            $store->dispo = true;
            foreach($product_stocks as $variant)
            {
                $stock = $variant->getStockByStore($store);
                if(!$stock || ($stock->getOnHand() <= 0))
                {
                    $store->dispo = false;
                }
            }
        }
        return new Response($this->twig->render('store/shipment_list.html.twig', [
            'stores' => $stores,
        ]));
    }

    /**
     * @Route("/name/{id}", name="store_name")
     */
    public function storeNameAction(string $id, ManagerRegistry $managerRegistry): Response
    {
        $store = $managerRegistry->getRepository(Store::class)->find($id);
        return new Response($store ? $store->getName() : '', 200, ['Content-Type' => 'text/html']);
    }

    /**
     * @Route("/test", name="store_test")
     */
    public function testAction(GinkoiaHelper $ginkoiaHelper)
    {
        $order = $this->container->get('doctrine')->getRepository(Order::class)->find(8);
        
        echo $ginkoiaHelper->export($order);

        die;
    }

    /**
     * @Route("/{slug}", name="store_view")
     */
    public function viewAction(Request $request): Response
    {
        $slug = $request->get('slug');
        if(!in_array($slug, ['antibes', 'metz', 'toulouse', 'bordeaux']))
        {
            return $this->redirectToRoute('chk_store_action_index');
        }

        // default
        $store = null;

        return new Response($this->twig->render('store/view.html.twig', [
            'store' => $store,
            'slug' => $slug
        ]));
    }
}
