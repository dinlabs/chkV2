<?php

declare(strict_types=1);

namespace App\Controller\Chullanka;

use App\Entity\Chullanka\Store;
use App\Entity\Order\Order;
use App\Entity\Product\Product;
use App\Entity\Product\ProductVariant;
use BitBag\SyliusElasticsearchPlugin\Form\Type\ShopProductsFilterType;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment;

final class StoreController extends AbstractController
{
    /** @var ManagerRegistry */
    private $managerRegistry;

    /** @var Environment */
    private $twig;

    public function __construct(ManagerRegistry $managerRegistry, Environment $twig)
    {
        $this->managerRegistry = $managerRegistry;
        $this->twig = $twig;
    }

    /**
     * @Route("/", name="store_index")
     */
    public function indexAction(Request $request): Response
    {
        $template = $request->get('template') ?? 'chullanka/store/index.html.twig';
        $stores = $this->managerRegistry->getRepository(Store::class)->findAll();
        return new Response($this->twig->render($template, [
            'stores' => $stores
        ]));
    }

    /**
     * @Route("/shipment_list/{id_order}", name="store_shipment_list")
     */
    public function shipmentListAction(string $id_order): Response
    {
        $order = $this->managerRegistry->getRepository(Order::class)->find($id_order);
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
                        $ppVariant = $this->managerRegistry->getRepository(ProductVariant::class)->find($ppvid);
                        $product_stocks[] = $ppVariant;
                    }
                    continue;//on ne prend pas en compte les infos du pack lui-même
                }
            }
            $product_stocks[] = $variant;
        }

        $stores = $this->managerRegistry->getRepository(Store::class)->findAll();
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
        return new Response($this->twig->render('chullanka/store/shipment_list.html.twig', [
            'stores' => $stores,
        ]));
    }

    /**
     * @Route("/name/{id}", name="store_name")
     */
    public function storeNameAction(string $id): Response
    {
        $store = $this->managerRegistry->getRepository(Store::class)->find($id);
        return new Response($store ? $store->getName() : '', 200, ['Content-Type' => 'text/html']);
    }

    /**
     * has to be the last route!
     * @Route("/{code}", name="store_view")
     */
    public function viewAction(Request $request, FormFactoryInterface $formFactory): Response
    {
        $code = $request->get('code');
        $store = $this->managerRegistry->getRepository(Store::class)->findOneByCode($code);
        return new Response($this->twig->render('chullanka/store/view.html.twig', [
            'store' => $store,
            'products' => [],
        ]));
    }
}
