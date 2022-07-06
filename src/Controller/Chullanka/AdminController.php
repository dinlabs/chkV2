<?php

namespace App\Controller\Chullanka;

use App\Entity\Chullanka\Brand;
use App\Entity\Chullanka\Parameter;
use App\Entity\Product\Product;
use App\Entity\Shipping\Shipment;
use App\Service\GinkoiaCustomerWs;
use App\Service\IzyproHelper;
use App\Service\Target2SellHelper;
use App\Service\UpstreamPayWidget;
use Cloudflare\API\Auth\APIKey as CFAPIKey;
use Cloudflare\API\Adapter\Guzzle as CFGuzzle;
use Cloudflare\API\Endpoints\Zones as CFZones;
use Doctrine\ORM\EntityManagerInterface;
use SM\Factory\FactoryInterface;
use Sylius\Component\Core\OrderShippingTransitions;
use Sylius\Component\Shipping\ShipmentTransitions;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;

class AdminController extends AbstractController
{

    public function searchProduct(Request $request)
    {
        $repo = $this->container->get('doctrine')->getRepository(Product::class);

        $criteria = $request->query->get('criteria');
        $q = $criteria['search']['value'];

        $items = $repo->findBySearch($q);

        return new JsonResponse([
            '_embedded' => [
                'items' => $items,
            ]
        ]);
    }

    /**
     * @Route("/ajax/brands/search", name="chk_admin_ajax_brand_by_name_phrase")
     */
    public function searchBrandByPhrase(Request $request, EntityManagerInterface $em)
    {
        $phrase = $request->get('phrase');

        $brands = $em->getRepository(Brand::class)->findByPhrase($phrase);
        $datas = [];

        foreach ($brands as $brand) {
            $datas[] = [
                'id' => $brand->getId(),
                'name' => $brand->getName(),
                'code' => $brand->getCode()
            ];
        }

        return new JsonResponse($datas);
    }

    /**
     * @Route("/ajax/brands/code", name="chk_admin_ajax_brand_by_code")
     */
    public function searchBrandByCode(Request $request, EntityManagerInterface $em)
    {
        $code = $request->get('code');
        $brand = $em->getRepository(Brand::class)->findOneByCode($code);

        if ($brand === null) {
            return new JsonResponse([]);
        }

        $data = [
            'id' => $brand->getId(),
            'name' => $brand->getName(),
            'code' => $brand->getCode()
        ];

        return new JsonResponse($data);
    }

    /**
     * @Route("/ordershipstate/{id}", name="chk_admin_change_order_ship_state")
     */
    public function changeOrderInStoreState(Request $request, FactoryInterface $stateMachineFactory)
    {
        if(($id = $request->get('id')) && ($transition = $request->get('transition')) && !empty($transition))
        {
            if($shipment = $this->container->get('doctrine')->getRepository(Shipment::class)->find($id))
            {
                $stateMachine = $stateMachineFactory->get($shipment, ShipmentTransitions::GRAPH);
                if($stateMachine->can($transition)) 
                {
                    $stateMachine->apply($transition);
                }

                $order = $shipment->getOrder();
                $stateMachine = $stateMachineFactory->get($order, OrderShippingTransitions::GRAPH);
                if($stateMachine->can($transition)) 
                {
                    $stateMachine->apply($transition);
                }

                $em = $this->container->get('doctrine')->getManager();
                $em->flush();
            }
        }

        // retour à la page
        $referer = $request->headers->get('referer');
        return $this->redirect($referer);

    }

    /**
     *
     * @Route("/test/izypro", name="test_izypro")
     */
    public function testSftpIzypro(Request $request, IzyproHelper $izyproHelper)
    {
        $files = $izyproHelper->showFiles();
        return $this->render('@SyliusAdmin/Chullanka/izypro.html.twig', [
            'files' => $files,
        ]);
    }

    /**
     *
     * @Route("/test/ginkoia", name="test_ginkoia")
     */
    public function testExportsGinkoia(Request $request)
    {
        $msg = '';
        $files = [];
        $exportPath = $this->chkParameter('ginkoia-path-export');
        if(is_dir($exportPath))
        {
            $files = scandir($exportPath); // liste des fichiers dans le rep. d'import
        }
        else $msg  = "Ce répertoire n'existe pas";
        
        return $this->render('@SyliusAdmin/Chullanka/ginkoiaexports.html.twig', [
            'exportpath' => $exportPath,
            'files' => $files,
            'msg' => $msg,
        ]);
    }

    /**
     *
     * @Route("/test/ginkoiaws", name="test_ginkoiaws")
     */
    public function testWSGinkoia(Request $request, GinkoiaCustomerWs $ginkoiaCustomerWs)
    {
        $email = $request->query->get('email') ?: 'quentin.maes@chullanka.com'; //bestrenov@hotmail.com
        $infos = $ginkoiaCustomerWs->getCustomerInfos($email);
        $loyalties = $ginkoiaCustomerWs->getCustomerLoyalties($email);

        $shoporders = [];
        $return = $ginkoiaCustomerWs->getCustomerShopOrders($email);
        if(!is_string($return))
        {
            foreach($return as $order)
            {
                $datas = ['Order' => $order];
                $receiptId = $order['ReceiptID'];
                if($orderItems = $ginkoiaCustomerWs->getCustomerReceiptDetail($receiptId))
                {
                    $datas['Details'] = $orderItems;
                }
                $shoporders[] = $datas;
            }
        }

        return $this->render('@SyliusAdmin/Chullanka/ginkoiaws.html.twig', [
            'email' => $email,
            'infos' => $infos,
            'loyalties' => $loyalties,
            'shoporders' => $shoporders,
        ]);
    }

    /**
     *
     * @Route("/test/uspsession", name="test_uspsession")
     */
    public function testUspSession(Request $request, UpstreamPayWidget $upstreamPayWidget)
    {
        $sessionUspId = '';
        $infos = [];
        if($sessionUspId = $request->query->get('sessionid'))
        {
            $infos = $upstreamPayWidget->getSessionInfos($sessionUspId);
        }
        return $this->render('@SyliusAdmin/Chullanka/uspsession.html.twig', [
            'sessionid' => $sessionUspId,
            'infos' => $infos
        ]);
    }

    public function exportCatalogT2S(Request $request, Target2SellHelper $target2SellHelper)
    {
        //$target2SellHelper->exportCatalog();
        //$target2SellHelper->updateProductRanks();
        die;
    }

    /**
     *
     * @Route("/cloudflare/flush", name="cloudflare_flush")
     */
    public function flushCloudflare(Request $request)
    {
        /** @var FlashBagInterface $flashBag */
        $flashBag = $request->getSession()->getBag('flashes');

        $email = $this->chkParameter('cloudflare-email');//'yannick.lepetit@gmail.com'
        $apiKey = $this->chkParameter('cloudflare-api-key');//'1fcfb3079338c456a9df9e61c569bd119a12f'
        $zoneName = $this->chkParameter('cloudflare-zone-name');//'dinlabs.com'

        if(empty($email) || empty($apiKey) || empty($zoneName))
        {
            $flashBag->add('error', 'Veuillez renseigner les informations de l\'API Cloudflare.');
        }
        else
        {
            // Cloudflare API
            $key = new CFAPIKey($email, $apiKey);
            $adapter = new CFGuzzle($key);
            $zones = new CFZones($adapter);
            $zoneId = $zones->getZoneId($zoneName);
            $zones->cachePurgeEverything($zoneId)
                ? $flashBag->add('success', 'Le cache de Cloudflare de la zone "' . $zoneName . '" a été vidé.')
                : $flashBag->add('error', 'Un problème s\'est produit avec l\'API de Cloudflare pour vider le cache.')
            ;
        }
        
        // retour à la page
        $referer = $request->headers->get('referer');
        return $this->redirect($referer);
    }

    /**
     *
     * @Route("/command/cache/clear", name="command_cache_clear")
     */
    public function command_cache_clear(Request $request, KernelInterface $kernel)
    {
        $return = $this->do_command($kernel, 'cache:clear');

        /** @var FlashBagInterface $flashBag */
        $flashBag = $request->getSession()->getBag('flashes');
        $flashBag->add('success', $return);

        // retour à la page
        $referer = $request->headers->get('referer');
        return $this->redirect($referer);
    }

    /**
     *
     * @Route("/command/cache/warmup", name="command_cache_warmup")
     */
    public function command_cache_warmup(Request $request, KernelInterface $kernel)
    {
        $return = $this->do_command($kernel, 'cache:warmup');

        /** @var FlashBagInterface $flashBag */
        $flashBag = $request->getSession()->getBag('flashes');
        $flashBag->add('success', $return);

        // retour à la page
        $referer = $request->headers->get('referer');
        return $this->redirect($referer);
    }

    /**
     *
     * @Route("/command/elastica/populate", name="command_elastica_populate")
     */
    public function command_elastica_populate(Request $request, KernelInterface $kernel)
    {
        $return = $this->do_command($kernel, 'fos:elastica:populate');

        /** @var FlashBagInterface $flashBag */
        $flashBag = $request->getSession()->getBag('flashes');
        $flashBag->add('success', $return);

        // retour à la page
        $referer = $request->headers->get('referer');
        return $this->redirect($referer);
    }

    private function do_command($kernel, $command)
    {
        $env = $kernel->getEnvironment();
        
        $application = new Application($kernel);
        $application->setAutoExit(false);
        
        $input = new ArrayInput(array(
            'command' => $command,
            '--env' => $env
        ));
        
        $output = new BufferedOutput();
        $application->run($input, $output);
        
        $content = $output->fetch();
        
        return $content;
        //return new Response($content);
    }

    /**
     * Return a parameter's value
     */
    private function chkParameter($slug)
    {
        return $this->container->get('doctrine')->getRepository(Parameter::class)->getValue($slug);
    }
}
