<?php

namespace App\EventSubscriber;

use App\Entity\Addressing\Address;
use App\Entity\Chullanka\HistoricOrder;
use App\Entity\Chullanka\Store;
use App\Entity\Payment\Payment;
use App\Entity\Payment\PaymentMethod;
use App\Service\GinkoiaCustomerWs;
use App\Service\GinkoiaHelper;
use Doctrine\ORM\EntityManagerInterface;
use SM\Factory\FactoryInterface;
use Sylius\Bundle\ResourceBundle\Event\ResourceControllerEvent;
use Sylius\Component\Core\OrderCheckoutTransitions;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\SecurityEvents;
use Symfony\Component\String\Slugger\SluggerInterface;

class EventSubscriber implements EventSubscriberInterface
{
    private $entityManager;
    private $session;
    private $slugger;
    private $ginkoiaHelper;
    private $ginkoiaCustomerWs;
    private $stateMachineFactory;

    public function __construct(EntityManagerInterface $entityManager, SessionInterface $session, SluggerInterface $slugger, GinkoiaHelper $ginkoiaHelper, GinkoiaCustomerWs $ginkoiaCustomerWs, FactoryInterface $stateMachineFactory)
    {
        $this->entityManager = $entityManager;
        $this->session = $session;
        $this->slugger = $slugger;
        $this->ginkoiaHelper = $ginkoiaHelper;
        $this->ginkoiaCustomerWs = $ginkoiaCustomerWs;
        $this->stateMachineFactory = $stateMachineFactory;
    }

    public static function getSubscribedEvents()
    {
        //$eventName = $configuration->getEvent() ?: CartActions::ADD;
        //$metadata = $configuration->getMetadata();
        //error_log(sprintf('%s.%s.pre_%s', $metadata->getApplicationName(), $metadata->getName(), $eventName));


        return [
            'sylius.product.pre_create' => 'onSyliusProductPreCreUpdate',
            'sylius.product.pre_update' => 'onSyliusProductPreCreUpdate',
            'sylius.order.pre_update' => 'onSyliusOrderPreAdd',
            'sylius.order_item.post_add' => 'onSyliusOrderItemAddToCart',
            'sylius.order.post_select_shipping' => 'onSyliusOrderPostSelectShipping',
            'sylius.order.post_complete' => 'onSyliusOrderPostComplete',

            'app.brand.pre_create' => 'onAppBrandPreCreUpdate',
            'app.brand.pre_update' => 'onAppBrandPreCreUpdate',
            'app.chulli.pre_create' => 'onAppChulliPreCreUpdate',
            'app.chulli.pre_update' => 'onAppChulliPreCreUpdate',
            'app.store.pre_create' => 'onAppStorePreCreUpdate',
            'app.store.pre_update' => 'onAppStorePreCreUpdate',
            'app.store_service.pre_create' => 'onAppStoreServicePreCreUpdate',
            'app.store_service.pre_update' => 'onAppStoreServicePreCreUpdate',
            'app.parameter.pre_create' => 'onAppParameterPreCreate',

            //'security.interactive_login' => 'onSecurityInteractiveLogin',
            SecurityEvents::INTERACTIVE_LOGIN => 'onSecurityInteractiveLogin',

            // call Ginkoia WS
            'sylius.customer.post_register' => 'onSyliusCustomerPostSave',
            'sylius.customer.post_update' => 'onSyliusCustomerPostSave',
            'sylius.address.post_register' => 'onSyliusCustomerPostSave',
            'sylius.address.post_update' => 'onSyliusCustomerPostSave',
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

        $complementary = $subject->getComplementaryProduct();
        if(!$complementary || (empty($complementary->getTitle())))
        {
            //ne créé pas de ComplementaryProduct si le titre et la description n'ont pas été renseigné
            $subject->setComplementaryProduct(null);
        }
        else
        {
            if($backgroundFile = $complementary->getBackgroundFile())
            {
                $originalFilename = pathinfo($backgroundFile->getClientOriginalName(), PATHINFO_FILENAME);
                $newFilename = strtolower($this->slugger->slug($originalFilename).'-'.uniqid().'.'.$backgroundFile->guessExtension());
                $path = 'upload/complementary/backgrounds';
                try {
                    $backgroundFile->move($path, $newFilename);
                } catch (FileException $e) {
                    error_log(print_r($e, true));
                }
                if(!empty($complementary->getBackground()))
                {
                    @unlink( rtrim($path, '/\\').\DIRECTORY_SEPARATOR.$complementary->getBackground() );
                }
                $complementary->setBackground($newFilename);
            }
        }

        if($faqs = $subject->getFaqs())
        {
            foreach($faqs as $faq)
            {
                $this->entityManager->persist($faq);
            }
            $this->entityManager->flush();
        }
    }


    /**
     * Ajout au panier
     */
    public function onSyliusOrderItemAddToCart(GenericEvent $event)
    {
        //error_log("onSyliusOrderItemAddToCart");
        $data = [];
        $orderItem = $event->getSubject();//OrderItem::class

        $variant = $orderItem->getVariant();
        $data['variant_id'] = $variant->getId();

        $data['error'] = '';
        if(($order = $orderItem->getOrder()) && $order->isPanierMixte())
        {
            //todo; retirer le message "bien ajouté"!
            $flash = $this->session->getFlashBag()->get('success');
            
            $this->session->getFlashBag()->add('error', 'C\'est un panier mixte');
            
            $order->removeItem($orderItem);
            $this->entityManager->persist($order);
            $this->entityManager->flush();
            
            $data['error'] = 'paniermixte';
        }
        $event->setResponse(new JsonResponse($data));
    }

    /**
     * Maj du panier
     */
    public function onSyliusOrderPreAdd(GenericEvent $event)
    {
        //error_log("onSyliusOrderPreAdd");
        $order = $event->getSubject();

		if($order->isPanierMixte())
        {
            $this->session->getFlashBag()->add('error', 'Panier mixte');
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

            //force paymethod UpStreamPay - a priori pas utile vu qu'il n'y a qu'une seule méthode
            /*if($payMethod = $this->entityManager->getRepository(PaymentMethod::class)->findOneByCode('UPSTREAM_PAY'))
            {
                if($order->getPayments())
                {
                    // on vérifie 
                    $payment = $order->getPayments()->first();
                    if($payment->getMethod()->getCode() != 'UPSTREAM_PAY')
                    {
                        // si ça n'est pas upstream, on force cette méthode
                        $payment->setMethod($payMethod);
                    }
                }
                else 
                {
                    error_log("on va créer la methode");
                    //create Payment
                    //$payment = new Payment();
                    //$payment->setMethod($payMethod);
                    //$order->addPayment($payment);
                }
            }*/

            // changer le state
            $stateMachine = $this->stateMachineFactory->get($order, OrderCheckoutTransitions::GRAPH);
            $stateMachine->apply(OrderCheckoutTransitions::TRANSITION_SELECT_PAYMENT);

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


    /**
     * Gère l'upload du logo et de l'image de fond d'une marque
     */
    public function onAppBrandPreCreUpdate(GenericEvent $event): void
    {
        $brand = $event->getSubject();
        if($logoFile = $brand->getLogoFile())
        {
            $originalFilename = pathinfo($logoFile->getClientOriginalName(), PATHINFO_FILENAME);
            $newFilename = strtolower($this->slugger->slug($originalFilename).'-'.uniqid().'.'.$logoFile->guessExtension());
            
            // Move the file to the directory where brochures are stored
            $path = 'upload/brand/logos';
            try {
                $logoFile->move($path, $newFilename);
            } catch (FileException $e) {
                // ... handle exception if something happens during file upload
                error_log(print_r($e, true));
            }
            if(!empty($brand->getLogo()))
            {
                //delete file
                @unlink( rtrim($path, '/\\').\DIRECTORY_SEPARATOR.$brand->getLogo() );
            }
            $brand->setLogo($newFilename);
        }
        if($backgroundFile = $brand->getBackgroundFile())
        {
            $originalFilename = pathinfo($backgroundFile->getClientOriginalName(), PATHINFO_FILENAME);
            $newFilename = strtolower($this->slugger->slug($originalFilename).'-'.uniqid().'.'.$backgroundFile->guessExtension());
            $path = 'upload/brand/backgrounds';
            try {
                $backgroundFile->move($path, $newFilename);
            } catch (FileException $e) {
                error_log(print_r($e, true));
            }
            if(!empty($brand->getBackground()))
            {
                @unlink( rtrim($path, '/\\').\DIRECTORY_SEPARATOR.$brand->getBackground() );
            }
            $brand->setBackground($newFilename);
        }
        if($productBackgroundFile = $brand->getProductBackgroundFile())
        {
            $originalFilename = pathinfo($productBackgroundFile->getClientOriginalName(), PATHINFO_FILENAME);
            $newFilename = strtolower($this->slugger->slug($originalFilename).'-'.uniqid().'.'.$productBackgroundFile->guessExtension());
            $path = 'upload/brand/backgrounds';
            try {
                $productBackgroundFile->move($path, $newFilename);
            } catch (FileException $e) {
                error_log(print_r($e, true));
            }
            if(!empty($brand->getProductBackground()))
            {
                @unlink( rtrim($path, '/\\').\DIRECTORY_SEPARATOR.$brand->getProductBackground() );
            }
            $brand->setProductBackground($newFilename);
        }
        $this->entityManager->persist($brand);
        $this->entityManager->flush();
    }

    /**
     * Gère l'upload de l'avatar d'un chulli
     */
    public function onAppChulliPreCreUpdate(GenericEvent $event): void
    {
        $chulli = $event->getSubject();
        if($avatarFile = $chulli->getAvatarFile())
        {
            $originalFilename = pathinfo($avatarFile->getClientOriginalName(), PATHINFO_FILENAME);
            $newFilename = strtolower($this->slugger->slug($originalFilename).'-'.uniqid().'.'.$avatarFile->guessExtension());
            $path = 'upload/chullis';
            try {
                $avatarFile->move($path, $newFilename);
            } catch (FileException $e) {
                error_log(print_r($e, true));
            }
            if(!empty($chulli->getAvatar()))
            {
                @unlink( rtrim($path, '/\\').\DIRECTORY_SEPARATOR.$chulli->getAvatar() );
            }
            $chulli->setAvatar($newFilename);
        }
        $this->entityManager->persist($chulli);
        $this->entityManager->flush();
    }

    /**
     * Gère l'upload de l'image de fond d'un magasin
     */
    public function onAppStorePreCreUpdate(GenericEvent $event): void
    {
        $store = $event->getSubject();
        if($backgroundFile = $store->getBackgroundFile())
        {
            $originalFilename = pathinfo($backgroundFile->getClientOriginalName(), PATHINFO_FILENAME);
            $newFilename = strtolower($this->slugger->slug($originalFilename).'-'.uniqid().'.'.$backgroundFile->guessExtension());
            $path = 'upload/store/backgrounds';
            try {
                $backgroundFile->move($path, $newFilename);
            } catch (FileException $e) {
                error_log(print_r($e, true));
            }
            if(!empty($store->getBackground()))
            {
                @unlink( rtrim($path, '/\\').\DIRECTORY_SEPARATOR.$store->getBackground() );
            }
            $store->setBackground($newFilename);
        }
        $this->entityManager->persist($store);
        $this->entityManager->flush();
    }
    
    /**
     * Gère l'upload du visuel du service
     */
    public function onAppStoreServicePreCreUpdate(GenericEvent $event): void
    {
        $storeService = $event->getSubject();
        if($thumbnailFile = $storeService->getThumbnailFile())
        {
            $originalFilename = pathinfo($thumbnailFile->getClientOriginalName(), PATHINFO_FILENAME);
            $newFilename = strtolower($this->slugger->slug($originalFilename).'-'.uniqid().'.'.$thumbnailFile->guessExtension());
            $path = 'upload/store/services';
            try {
                $thumbnailFile->move($path, $newFilename);
            } catch (FileException $e) {
                error_log(print_r($e, true));
            }
            if(!empty($storeService->getThumbnail()))
            {
                @unlink( rtrim($path, '/\\').\DIRECTORY_SEPARATOR.$storeService->getThumbnail() );
            }
            $storeService->setThumbnail($newFilename);
        }
        $this->entityManager->persist($storeService);
        $this->entityManager->flush();
    }
    
    /**
     * Gère le slug du parametre
     */
    public function onAppParameterPreCreate(GenericEvent $event): void
    {
        $parameter = $event->getSubject();
        $name = $parameter->getName();
        $slug = strtolower($this->slugger->slug($name));
        $parameter->setSlug($slug);
        $this->entityManager->persist($parameter);
        $this->entityManager->flush();
    }
    

    public function onSecurityInteractiveLogin(InteractiveLoginEvent $event)
    {
        $connectedUser = $event->getAuthenticationToken()->getUser();
        if($connectedUser && method_exists($connectedUser, 'getCustomer') && ($customer = $connectedUser->getCustomer()))
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
}
