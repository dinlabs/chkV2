<?php

declare(strict_types=1);

namespace App\Controller\Chullanka;

use App\Entity\Chullanka\Store;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

final class StoreController extends AbstractController
{
    /** @var Environment */
    private $twig;

    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
    }

    public function indexAction(): Response
    {
        return new Response($this->twig->render('store/index.html.twig', [
            'controller_name' => 'StoreController',
        ]));
    }

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
