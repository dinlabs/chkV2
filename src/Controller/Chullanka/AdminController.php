<?php

namespace App\Controller\Chullanka;

use App\Entity\Shipping\Shipment;
use SM\Factory\FactoryInterface;
use Sylius\Component\Core\OrderShippingTransitions;
use Sylius\Component\Shipping\ShipmentTransitions;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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
}
