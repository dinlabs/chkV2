<?php

namespace App\Controller\Chullanka;

use App\Entity\Chullanka\Wishlist;
use App\Entity\Chullanka\WishlistProduct;
use App\Entity\Product\ProductVariant;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment;

class WishlistController extends AbstractController
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

    
    #[Route('/', name: 'chk_wishlist')]
    public function index(): Response
    {
        return $this->render('chullanka/wishlist/index.html.twig', [
            'controller_name' => 'WishlistController',
        ]);
    }

    #[Route('/add/{variant_id}', name: 'chk_wishlist_add_product')]
    public function addProduct(Request $request, string $variant_id): Response
    {
        if($customer = $this->getCurrentCustomer())
        {
            $doctrine = $this->container->get('doctrine');

            if($request->request->get('submit'))
            {
                $wishlist_id = $request->request->get('wishlist_id');
                if($wishlist_id != 0)
                {
                    $wishlist = $doctrine->getRepository(Wishlist::class)->find($wishlist_id);
                }
                else
                {
                    $wishlist = new Wishlist();
                    $wishlist->setCustomer($customer);

                    $newishlist = $request->request->get('newishlist');
                    if(empty($newishlist))
                    {
                        $newishlist = 'Ma liste d\'envie';
                    }
                    $wishlist->setName($newishlist);
                }
                $variant = $doctrine->getRepository(ProductVariant::class)->find($variant_id);
                $product = $variant->getProduct();

                $wishlistProduct = $doctrine->getRepository(WishlistProduct::class)->findOneBy([
                    'wishlist' => $wishlist,
                    'product' => $product,
                    'variant' => $variant
                ]);
                if($wishlistProduct)
                {
                    $qty = $wishlistProduct->getQuantity();
                    $qty++;
                    $wishlistProduct->setQuantity($qty);
                }
                else 
                {
                    $wishlistProduct = new WishlistProduct();
                    $wishlistProduct->setProduct($product);
                    $wishlistProduct->setVariant($variant);
                    $wishlistProduct->setQuantity(1);

                    $wishlist->addWishlistProduct($wishlistProduct);
                }
                
                $em = $doctrine->getManager();
                $em->persist($wishlistProduct);
                $em->persist($wishlist);
                $em->flush();
            }

            $wishlists = $customer->getWishlists();
            return $this->render('chullanka/wishlist/add_product.html.twig', [
                'variant_id' => $variant_id,
                'wishlists' => $wishlists
            ]);
        }
        return $this->render('chullanka/wishlist/add_product.html.twig', [
            'notuser' => true
        ]);
    }

    #[Route('/view/{id}', name: 'chk_wishlist_view')]
    public function view(Request $request, string $id): Response
    {
        $doctrine = $this->container->get('doctrine');
        $wishlist = $doctrine->getRepository(Wishlist::class)->find($id);
        if($wishlist)
        {
            if($wishlist->getCustomer() == $this->getCurrentCustomer())
            {
                return $this->render('chullanka/wishlist/view.html.twig', [
                    'wishlist' => $wishlist
                ]); 
            }
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