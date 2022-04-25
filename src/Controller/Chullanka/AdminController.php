<?php

namespace App\Controller\Chullanka;

use App\Entity\Shipping\Shipment;
use SM\Factory\FactoryInterface;
use Sylius\Component\Core\OrderShippingTransitions;
use Sylius\Component\Shipping\ShipmentTransitions;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;

class AdminController extends AbstractController
{
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

    public function flushCloudflare()
    {
        /*
        $key = new Cloudflare\API\Auth\APIKey('yannick.lepetit@gmail.com', '1fcfb3079338c456a9df9e61c569bd119a12f');
	    $adapter = new Cloudflare\API\Adapter\Guzzle($key);
	    $zones = new Cloudflare\API\Endpoints\Zones($adapter);
	    $zoneId = $zones->getZoneId('chullanka.com');
	    
	    $zones->cachePurgeEverything($zoneId)
	       ? $this->_getSession ()->addSuccess ('Le cache de Cloudflare a été vidé.')
           : $this->_getSession ()->addError ('Un problème s\'est produit avec l\'API de Cloudflare pour vider le cache.')
        ;
        */
    }

    /**
     *
     * @Route("/command/cache/clear", name="command_cache_clear")
     */
    public function command_cache_clear(KernelInterface $kernel)
    {
        return $this->do_command($kernel, 'cache:clear');
    }

    /**
     *
     * @Route("/command/cache/warmup", name="command_cache_warmup")
     */
    public function command_cache_warmup(KernelInterface $kernel)
    {
        return $this->do_command($kernel, 'cache:warmup');
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
        
        return new Response($content);
    }
}
