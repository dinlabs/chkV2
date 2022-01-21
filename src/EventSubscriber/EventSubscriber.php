<?php

namespace App\EventSubscriber;

use App\Entity\Chullanka\Store;
use App\Service\GinkoiaHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

class EventSubscriber implements EventSubscriberInterface
{
    private $entityManager;
    private $ginkoiaHelper;

    public function __construct(EntityManagerInterface $entityManager, GinkoiaHelper $ginkoiaHelper)
    {
        $this->entityManager = $entityManager;
        $this->ginkoiaHelper = $ginkoiaHelper;
    }

    public static function getSubscribedEvents()
    {
        //$eventName = $configuration->getEvent() ?: CartActions::ADD;
        //$metadata = $configuration->getMetadata();
        //error_log(sprintf('%s.%s.pre_%s', $metadata->getApplicationName(), $metadata->getName(), $eventName));


        return [
            'sylius.product.pre_create' => 'onSyliusProductPreCreUpdate',
            'sylius.product.pre_update' => 'onSyliusProductPreCreUpdate',
            'sylius.order.post_select_shipping' => 'onSyliusOrderPostSelectShipping',
            'sylius.order.post_complete' => 'onSyliusOrderPostComplete',
            
            'sylius.order_item.pre_add' => 'onSyliusOrderItemPreAdd',
            'sylius.order.pre_add' => 'onSyliusOrderPreAdd',
        ];
    }

    /**
     * Appelé quand on enregistre un produit (notamment dans le BO)
     */
    public function onSyliusProductPreCreUpdate(GenericEvent $event)
    {
        $subject = $event->getSubject();
        $chulltest = $subject->getChulltest();
        if(!$chulltest || (empty($chulltest->getDate()) || empty($chulltest->getDescription())))
        {
            //ne créé de Chulltest si la date et la description n'ont pas été renseigné
            $subject->setChulltest(null);
        }
    }

    /**
     * Appelé dans le tunnel quand on valide son choix de transporteur
     */
    public function onSyliusOrderPostSelectShipping(GenericEvent $event)
    {
        $order = $event->getSubject();
        if($shipment = $order->getShipments())
        {
            $shippingAddress = $order->getShippingAddress();
            $further = $order->getFurther();

            $shipping_method = $shipment->first()->getMethod()->getCode();
            $split_ship = explode('_', $shipping_method);
            $shipping_method_type = $split_ship[0];

            if($shipping_method_type == 'pickup')
            {
                if(isset($_POST['pickup_point']) && !empty($_POST['pickup_point']))
                {
                    $pickupInfos = explode('|||', $_POST['pickup_point']);

                    $company = $pickupInfos[0];
                    $address = $pickupInfos[1];
                    $zipcode = $pickupInfos[2];
                    $city = $pickupInfos[3];
                    $puid = $pickupInfos[4];

                    $shippingAddress->setCompany($company);
                    $shippingAddress->setStreet($address);
                    $shippingAddress->setPostcode($zipcode);
                    $shippingAddress->setCity($city);

                    $further = null;//on reinitialise
                    $further = ['pickup_id' => (string)$puid];
                }
            }
            elseif($shipping_method_type == 'store')
            {
                if(isset($_POST['ship_in_store']) && !empty($_POST['ship_in_store']))
                {
                    $id_store = (int)$_POST['ship_in_store'];

                    // get Store address to update shippingAddress
                    $store = $this->entityManager->getRepository(Store::class)->find($id_store);
                    if($store)
                    {
                        $company = $store->getName();
                        $address = $store->getStreet();
                        $zipcode = $store->getPostCode();
                        $city = $store->getCity();
                        
                        $shippingAddress->setCompany($company);
                        $shippingAddress->setStreet($address);
                        $shippingAddress->setPostcode($zipcode);
                        $shippingAddress->setCity($city);

                        if($phone = $store->getPhoneNumber())
                        {
                            $shippingAddress->setPhoneNumber($phone);
                        }
                    }
                    
                    $further = null;//on reinitialise
                    $further = ['store' => $id_store];
                }
            }
            else
            {
                if(!is_null($further) && (isset($further['pickup_id']) || isset($further['store'])))
                {
                    // si c'est pas null c'est que le client a déjà selectionné "pickup" ou "store" dans le passé pour cet Order
                    $further = null;//on reinitialise

                    //todo: voir comment remettre l'adresse de livraison par défaut si la personne revient en arrière et qu'elle choisit une livraison à domicile (surtout si elle avait sélectionner une adresse diff. de celle de facturation !)
    
                    $billingAddress = $order->getBillingAddress();
                    if($billingAddress->getStreet() != $shippingAddress->getStreet())
                    {
                        $shippingAddress->setCompany( $billingAddress->getCompany() );
                        $shippingAddress->setStreet( $billingAddress->getStreet() );
                        $shippingAddress->setPostcode( $billingAddress->getPostcode() );
                        $shippingAddress->setCity( $billingAddress->getCity() );
                        $shippingAddress->setPhoneNumber( $billingAddress->getPhoneNumber() );
    
                        // en changeant $order->setCheckoutState pour remmetre à "cart" afin de forcer à rechoisir l'adresse ?
                        $order->setCheckoutState('cart');
                    }
                }
            }
            $order->setFurther($further);
            $this->entityManager->flush();
        }

    }

    /**
     * Genère le XML de vente quand le Order est complété
     */
    public function onSyliusOrderPostComplete(GenericEvent $event)
    {
        $order = $event->getSubject();
        error_log($this->ginkoiaHelper->export($order));
    }



    public function onSyliusOrderItemPreAdd(GenericEvent $event)
    {
        error_log("onSyliusOrderItemPreAdd");
        $subject = $event->getSubject();//OrderItem::class
        //dd($subject);
        //$subject->getOrder() ==> null :-( !
    }

    public function onSyliusOrderPreAdd(GenericEvent $event)
    {
        error_log("onSyliusOrderPreAdd");
        $order = $event->getSubject();
        //dd($subject);
    }
}
