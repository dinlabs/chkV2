<?php

namespace App\EventSubscriber;

use App\Entity\Addressing\Address;
use App\Entity\Chullanka\HistoricOrder;
use App\Entity\Chullanka\Store;
use App\Service\GinkoiaCustomerWs;
use App\Service\GinkoiaHelper;
use Doctrine\ORM\EntityManagerInterface;
use Sylius\Bundle\ResourceBundle\Event\ResourceControllerEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\SecurityEvents;

class EventSubscriber implements EventSubscriberInterface
{
    private $entityManager;
    private $ginkoiaHelper;
    private $ginkoiaCustomerWs;

    public function __construct(EntityManagerInterface $entityManager, GinkoiaHelper $ginkoiaHelper, GinkoiaCustomerWs $ginkoiaCustomerWs)
    {
        $this->entityManager = $entityManager;
        $this->ginkoiaHelper = $ginkoiaHelper;
        $this->ginkoiaCustomerWs = $ginkoiaCustomerWs;
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

            //'security.interactive_login' => 'onSecurityInteractiveLogin',
            SecurityEvents::INTERACTIVE_LOGIN => 'onSecurityInteractiveLogin',

            // call Ginkoia WS
            'sylius.customer.post_register' => 'onSyliusCustomerPostSave',
            'sylius.customer.post_update' => 'onSyliusCustomerPostSave',
            'sylius.address.post_register' => 'onSyliusCustomerPostSave',
            'sylius.address.post_update' => 'onSyliusCustomerPostSave',

            
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

                    $shippingAddress->setCustomer(null);
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
                        
                        $shippingAddress->setCustomer(null);
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
                        //$shippingAddress->setCustomer( $billingAddress->getCustomer() );
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


    public function onSecurityInteractiveLogin(InteractiveLoginEvent $event)
    {
        
        $connectedUser = $event->getAuthenticationToken()->getUser();
        if($connectedUser && ($customer = $connectedUser->getCustomer()))
        {
            $webserv = $this->ginkoiaCustomerWs;

            // Todo:On interroge le WS pour mettre à jour les infos du client sur le site
            

            // On interroge le WS pour récupérer ses commandes en magasin via son email
            if($orders = $webserv->getCustomerShopOrders($customer->getEmail()))
            {
                foreach($orders as $order)
                {
                    $receiptId = $order['ReceiptID'];

                    // On cherche si ce order a deja ete importé
                    if($this->entityManager->getRepository(HistoricOrder::class)->findOneBy([
                        'customer' => $customer,
                        'order_id' => $receiptId
                    ])) continue;// si oui, on ne fait rien et on passe au suivant

                    //toujours là, on va créer une entrée;
                    if($orderItems = $webserv->getCustomerReceiptDetail($receiptId))
                    {
                        $items = [];
                        foreach($orderItems as $orderItem)
                        {
                            $items[] = [
                                'name' => $orderItem['Name'] . ' - ' . $orderItem['Brand'],
                                'reference' => $orderItem['Reference'],
                                'code_chono' => $orderItem['Chrono'],
                                'quantity' => $orderItem['Quantity'],
                                'price' => (float)$orderItem['UnitNetPrice'] * 100,
                            ];
                        }

                        $_date = $order['ReceiptDate'];// au format ==> 01//06//2021 (voire 01////06////2021 !)
                        while(strpos($_date, '//') > -1)
                        {
                            $_date = str_replace('//', '/', $_date);
                        }
                        $_tmp = explode('/', $_date);// transformation en tableau
                        rsort($_tmp);// inverse l'ordre
                        $_date = new \Datetime( implode('-', $_tmp));// récupération au format ==> 2021-06-01

                        $historic = new HistoricOrder();
                        $historic   ->setCustomer($customer)
                                    ->setOrigin('magasin')
                                    ->setOrderId($receiptId)
                                    ->setSku($order['ReceiptNumber'])
                                    ->setOrderDate($_date)
                                    ->setItems($items)
                                    ->setAddress('')
                                    ->setShipment($order['ReceiptShop'])
                                    ->setShipmentPrice(0)
                                    ->setShipmentDate($_date)
                                    ->setTotal( (float)$order['ReceiptAmount'] * 100)
                                    ->setPaymethod($order['Payments'][0]['PaymentName'])
                                    ->setInvoice('')
                        ;
                        $this->entityManager->persist($historic);
                    }
                }
                $this->entityManager->flush();
            }
        }
    }

    /**
     * Envoi des infos du client au WS suite à màj sur le site
     */
    public function onSyliusCustomerPostSave(ResourceControllerEvent $event)
    {
        $subject = $event->getSubject();
        if($subject instanceof Address)
        {
            $customer = $subject->getCustomer();
        }
        else $customer = $subject;
        if(!$customer) return;

        $email = $customer->getEmail();

        // On interroge le WS pour savoir si le client y existe via son email
        $webserv = $this->ginkoiaCustomerWs;
        if(!($user = $webserv->getCustomerInfos($customer->getEmail())) || !isset($user['ID']))
        {
            // sinon on va le créer
            $user = [];
        }

        // User
        $user['IDWEB'] = $customer->getId();
        $user['Nom'] = $customer->getLastname();
        $user['Prenom'] = $customer->getFirstname();
        if($birthday = $customer->getBirthday())
        {
            $user['DateAnniversaire'] = $birthday->format('d/m/Y');
        }
        
        // Billing
        $billingAddress = $customer->getDefaultAddress();
        $user['FactureAdresse']['Mail'] = $email;
        //$user['FactureAdresse']['Mobile'] = $billingAddress->getMobile();
        $user['FactureAdresse']['Telephone'] = $billingAddress->getPhoneNumber();
        //$user['FactureAdresse']['Fax'] = $billingAddress->getFax();
        $user['FactureAdresse']['Ligne'] = $billingAddress->getStreet();
        $user['FactureAdresse']['Code'] = $billingAddress->getPostcode();
        $user['FactureAdresse']['Ville'] = $billingAddress->getCity();
        
        $billCountryCode = $billingAddress->getCountryCode();
        $user['FactureAdresse']['CodePays'] = $billCountryCode;
        /*$billCountry = Mage::getModel('directory/country')->load($billCountryId);
        $user['FactureAdresse']['Pays'] = $billCountry->getName();*/
        
        
        // Shipping
        $shippingAddress = $customer->getDefaultAddress();
        $shippingAddress = $billingAddress;
        $user['Adresse']['Mail'] = $email;
        //$user['Adresse']['Mobile'] = $shippingAddress->getMobile();
        $user['Adresse']['Telephone'] = $shippingAddress->getPhoneNumber();
        //$user['Adresse']['Fax'] = $shippingAddress->getFax();
        $user['Adresse']['Ligne'] = $shippingAddress->getStreet();
        $user['Adresse']['Code'] = $shippingAddress->getPostcode();
        $user['Adresse']['Ville'] = $shippingAddress->getCity();
        
        $shipCountryCode = $shippingAddress->getCountryCode();
        $user['Adresse']['CodePays'] = $shipCountryCode;
        /*$shipCountry = Mage::getModel('directory/country')->load($shipCountryId);
        $user['Adresse']['Pays'] = $shipCountry->getName();*/
        

        // Appel du WebService
        if($return = $webserv->setCustomerInfos($user, $email))
        {
            if(isset($return['ID']) && !empty($return['ID']))
            {
                error_log('Ginkoia obs::customerSaved : OK pour '.$email.' : ID : '.$return['ID']);
            }
            else
            {
                error_log('Ginkoia obs::customerSaved : PB : '.print_r($return,1));
            }
        }
        else 
        {
            error_log('Ginkoia obs::customerSaved : impossible de mettre à jour le WS pour : '.$email);
        }
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
