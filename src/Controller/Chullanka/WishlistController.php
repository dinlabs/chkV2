<?php

namespace App\Controller\Chullanka;

use App\Entity\Chullanka\Wishlist;
use App\Entity\Chullanka\WishlistProduct;
use App\Entity\Product\ProductVariant;
use App\Form\Type\WishlistType;
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
        return $this->redirectToRoute('sylius_shop_homepage');
    }

    #[Route('/create', name: 'chk_wishlist_create')]
    public function createWishlist(Request $request): Response
    {
        if($customer = $this->getCurrentCustomer())
        {
            $wishlist = new Wishlist();
            $wishlist->setCustomer($customer);

            $form = $this->createForm(WishlistType::class, $wishlist);
            $form->handleRequest($request);
            if($form->isSubmitted() && $form->isValid())
            {
                $doctrine = $this->container->get('doctrine');
                $em = $doctrine->getManager();
                $em->persist($wishlist);
                $em->flush();

                return $this->redirectToRoute('sylius_shop_account_dashboard');
            }

            return $this->render('chullanka/wishlist/create.html.twig', [
                'form' => $form->createView(),
            ]);
        }
        return $this->redirectToRoute('sylius_shop_account_dashboard');
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

                $flashBag = $request->getSession()->getBag('flashes');
                $flashBag->add('success', 'Le produit a été ajouté à votre liste !');

                return $this->redirectToRoute('sylius_shop_product_show', ['slug' => $product->getSlug(), '_locale' => $product->getTranslation()->getLocale()]);
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
                foreach($wishlist->getWishlistProducts() as $wishlistProduct)
                {
                    $wishlistProduct->delete_form = $this->createDeleteProductForm($wishlistProduct)->createView();
                }

                $delete_form = $this->createDeleteForm($wishlist);

                return $this->render('chullanka/wishlist/view.html.twig', [
                    'wishlist' => $wishlist,
                    'delete_form' => $delete_form->createView(),
                ]); 
            }
        }
        return $this->redirectToRoute('sylius_shop_homepage');
    }

    /**
     * Creates a form to remove a list.
     */
    private function createDeleteForm(Wishlist $wishlist)
    {
        return $this->createFormBuilder($wishlist, ['attr' => ['id' => 'delete_wishlist']])
            ->setAction($this->generateUrl('chk_wishlist_delete', array('id' => $wishlist->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }

    #[Route('/delete/{id}', name: 'chk_wishlist_delete', methods: ['DELETE'])]
    public function delete(Request $request, string $id): Response
    {
        $doctrine = $this->container->get('doctrine');
        $wishlist = $doctrine->getRepository(Wishlist::class)->find($id);
        if($wishlist && ($wishlist->getCustomer() == $this->getCurrentCustomer()))
        {
            $form = $this->createDeleteForm($wishlist);
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) 
            {
                $em = $doctrine->getManager();
                $em->remove($wishlist);
                $em->flush();
            }
        }
        return $this->redirectToRoute('sylius_shop_account_dashboard');
    }


    /**
     * Creates a form to remove a product from list.
     */
    private function createDeleteProductForm(WishlistProduct $wishlistProduct)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('chk_wishlist_remove_product', array('id' => $wishlistProduct->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }

    #[Route('/removeproduct/{id}', name: 'chk_wishlist_remove_product', methods: ['DELETE'])]
    public function removeProduct(Request $request, string $id): Response
    {
        $doctrine = $this->container->get('doctrine');
        $wishlistProduct = $doctrine->getRepository(WishlistProduct::class)->find($id);
        if($wishlistProduct)
        {
            $wishlist = $wishlistProduct->getWishlist();
            $form = $this->createDeleteProductForm($wishlistProduct);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) 
            {
                $em = $doctrine->getManager();
                $em->remove($wishlistProduct);
                $em->flush();
            }
        }
        return $this->redirectToRoute('chk_wishlist_view', ['id' => $wishlist->getId()]);
    }
    

    /**
     * Get current connected Customer
     */
    private function getCurrentCustomer()
    {
        return (($user = $this->getUser()) && ($customer = $user->getCustomer())) ? $customer : null;
    }
}