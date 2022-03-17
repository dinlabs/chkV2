<?php

declare(strict_types=1);

namespace App\Controller\Chullanka;

use App\Entity\Chullanka\Brand;
use App\Entity\Product\Product;
use App\Entity\Product\ProductAttribute;
#use App\Finder\ShopProductsFinder;
use App\Overrides\SyliusElasticsearchPlugin\Finder\ShopProductsFinder;
#use BitBag\SyliusElasticsearchPlugin\Finder\ShopProductsFinder;
use BitBag\SyliusElasticsearchPlugin\Controller\RequestDataHandler\DataHandlerInterface;
use BitBag\SyliusElasticsearchPlugin\Controller\RequestDataHandler\PaginationDataHandlerInterface;
use BitBag\SyliusElasticsearchPlugin\Controller\RequestDataHandler\SortDataHandlerInterface;
use App\Overrides\SyliusElasticsearchPlugin\Form\Type\ShopProductsFilterType;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment;

final class BrandController extends AbstractController
{
    /** @var ManagerRegistry */
    private $managerRegistry;

    /** @var Environment */
    private $twig;

    /** @var DataHandlerInterface */
    private $shopProductListDataHandler;

    /** @var SortDataHandlerInterface */
    private $shopProductsSortDataHandler;

    /** @var PaginationDataHandlerInterface */
    private $paginationDataHandler;

    public function __construct(
        ManagerRegistry $managerRegistry, 
        Environment $twig, 
        DataHandlerInterface $shopProductListDataHandler,
        SortDataHandlerInterface $shopProductsSortDataHandler,
        PaginationDataHandlerInterface $paginationDataHandler,
    )
    {
        $this->managerRegistry = $managerRegistry;
        $this->twig = $twig;
        $this->shopProductListDataHandler = $shopProductListDataHandler;
        $this->shopProductsSortDataHandler = $shopProductsSortDataHandler;
        $this->paginationDataHandler = $paginationDataHandler;
    }

    /**
     * @Route("/", name="brand_index")
     */
    public function indexAction(): Response
    {
        $repo = $this->managerRegistry->getRepository(Brand::class);

        /*$attr = $this->managerRegistry->getRepository(ProductAttribute::class)->findOneByCode('couleurs');
        $results = $repo->getBrandsByAttributeViaProduct($attr);
        dd($results);*/

        $topBrands = $repo->getTopBrands();
        $letters = $repo->getBrandsListByLetter();
        return new Response($this->twig->render('chullanka/brand/index.html.twig', [
            'topBrands' => $topBrands,
            'letters' => $letters
        ]));
    }

    /*
     * @Route("/", name="brand_ajax_brands_by_phrase")
    
    public function searchAction(Request $request): Response
    {
        $phrase = $request->get('phrase');

        $brands = $this->managerRegistry->getRepository(Brand::class)->findByPhrase($phrase);
        return new Response($this->twig->render('chullanka/brand/index.html.twig', [
            'brands' => $brands
        ]));
    }
    */

    /**
     * @Route("/menubrandlist", name="brand_menu_list")
     */
    public function menuBrandListAction(): Response
    {
        $brands = $this->managerRegistry->getRepository(Brand::class)->findBy([], ['name' => 'ASC']);
        return new Response($this->twig->render('chullanka/brand/menu_items.html.twig', [
            'brands' => $brands
        ]));
    }

    /**
     * @Route("/topbrandlist", name="brand_top_list")
     */
    public function topBrandListAction(): Response
    {
        $brands = $this->managerRegistry->getRepository(Brand::class)->getTopBrands();
        return new Response($this->twig->render('chullanka/brand/top_items.html.twig', [
            'brands' => $brands
        ]));
    }

    /**
     * has to be the last route!
     * @Route("/{code}", name="brand_view")
     */
    public function viewAction(
        Request $request, 
        FormFactoryInterface $formFactory, 
        ShopProductsFinder $shopProductsFinder
    ): Response
    {
        $code = $request->get('code');
        $brand = $this->managerRegistry->getRepository(Brand::class)->findOneByCode($code);

        // Filters
        $form = $formFactory->create(ShopProductsFilterType::class);
        //$form = $this->createForm(ShopProductsFilterType::class);
        $form->handleRequest($request);
        $requestData = array_merge(
            $form->getData(),
            $request->query->all(),
            ['brand' => $brand->getEscode(), 'slug' => '', 'name' => null],
        );
        
        /*if (!$form->isValid()) {
            $requestData = $this->clearInvalidEntries($form, $requestData);
        }*/

        //default
        $data = [
            'name' => $requestData['name'],
            'product_taxons' => '',
            'sort' => [
                'price' => [
                    'order' => 'asc',
                    'unmapped_type' => 'keyword'
                ]
            ]
        ];

        $data = array_merge(
            $data,
            $this->shopProductListDataHandler->retrieveData($requestData),
            $this->shopProductsSortDataHandler->retrieveData($requestData),
            $this->paginationDataHandler->retrieveData($requestData)
        );
        //dd($data);

        $products = $shopProductsFinder->find($data);

        return new Response($this->twig->render('chullanka/brand/view.html.twig', [
            'brand' => $brand,
            'form' => $form->createView(),
            'products' => $products,
        ]));
    }
}