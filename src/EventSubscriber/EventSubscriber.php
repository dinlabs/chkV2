<?php

namespace App\EventSubscriber;

use App\Entity\Chullanka\Store;
use App\Entity\Product\Product;
use App\Entity\Promotion\Promotion;
use App\Service\GinkoiaHelper;
use App\Service\IzyproHelper;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use SM\Factory\FactoryInterface as SMFactoryInterface;
use Sylius\Component\Core\Model\AdjustmentInterface;
use Sylius\Component\Core\OrderCheckoutTransitions;
use Sylius\Component\Mailer\Sender\SenderInterface;
use Sylius\Component\Order\Factory\AdjustmentFactoryInterface;
use Sylius\Component\Order\Modifier\OrderItemQuantityModifierInterface;
use Sylius\Component\Order\Processor\OrderProcessorInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

class EventSubscriber implements EventSubscriberInterface
{
    private $entityManager;
    private $logger;
    private $session;
    private $slugger;
    private $emailSender;
    private $ginkoiaHelper;
    private $izyproHelper;
    private $stateMachineFactory;
    private $orderItemFactory;
    private $orderItemQuantityModifier;
    private $adjustmentFactory;
    private $orderProcessor;

    public function __construct(EntityManagerInterface $entityManager, LoggerInterface $logger, SessionInterface $session, SluggerInterface $slugger, SenderInterface $emailSender, GinkoiaHelper $ginkoiaHelper, IzyproHelper $izyproHelper, SMFactoryInterface $stateMachineFactory, FactoryInterface $orderItemFactory, OrderItemQuantityModifierInterface $orderItemQuantityModifier, AdjustmentFactoryInterface $adjustmentFactory, OrderProcessorInterface $orderProcessor)
    {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->session = $session;
        $this->slugger = $slugger;
        $this->emailSender = $emailSender;
        $this->ginkoiaHelper = $ginkoiaHelper;
        $this->izyproHelper = $izyproHelper;
        $this->stateMachineFactory = $stateMachineFactory;
        $this->orderItemFactory = $orderItemFactory;
        $this->orderItemQuantityModifier = $orderItemQuantityModifier;
        $this->adjustmentFactory = $adjustmentFactory;
        $this->orderProcessor = $orderProcessor;
    }

    public static function getSubscribedEvents()
    {
        //$eventName = $configuration->getEvent() ?: CartActions::ADD;
        //$metadata = $configuration->getMetadata();
        //error_log(sprintf('%s.%s.pre_%s', $metadata->getApplicationName(), $metadata->getName(), $eventName));


        return [
            'sylius.taxon.pre_create' => 'onSyliusTaxonPreCreUpdate',
            'sylius.taxon.pre_update' => 'onSyliusTaxonPreCreUpdate',
            'sylius.product.pre_create' => 'onSyliusProductPreCreUpdate',
            'sylius.product.pre_update' => 'onSyliusProductPreCreUpdate',
            'sylius.order.pre_update' => 'onSyliusOrderPreAdd',
            'sylius.order_item.post_add' => 'onSyliusOrderItemAddToCart',
            'sylius.order_item.pre_remove' => 'onSyliusOrderItemRemove',
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
        ];
    }

    /**
     * Appelé quand on enregistre un Taxon
     */
    public function onSyliusTaxonPreCreUpdate(GenericEvent $event)
    {
        $subject = $event->getSubject();
        if($redirection = $subject->getRedirection())
        {
            if(!$redirection->getParent() || ($redirection === $subject)) 
            {
                $subject->setRedirection(null);
            }
        }

        if($links = $subject->getSubLinks())
        {
            foreach($links as $link)
            {
                $this->entityManager->persist($link);
            }
            $this->entityManager->flush();
        }
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
                $path = 'media/complementary/backgrounds';
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
        $orderItemId = $orderItem->getId();

        $variant = $orderItem->getVariant();
        $data['variant_id'] = $variant->getId();

        $data['error'] = '';
        if($order = $orderItem->getOrder())
        {
            if($order->isPanierMixte())
            {

                //todo; retirer le message "bien ajouté"!
                $flash = $this->session->getFlashBag()->get('success');
                
                //$this->session->getFlashBag()->add('error', 'C\'est un panier mixte');
                
                $order->removeItem($orderItem);
                $this->entityManager->persist($order);
                $this->entityManager->flush();
                
                $data['error'] = 'paniermixte';
            }
            else
            {
                $channel = $order->getChannel();
                $channelCode = $channel->getCode();

                $product = $variant->getProduct();
                $productCode = $product->getCode();

                $totalPrice = 0;
                $allItemVariantCodes = [];
                foreach($order->getItems() as $item)
                {
                    $_variant = $item->getVariant();
                    $allItemVariantCodes[] = $_variant->getCode();

                    //$totalPrice += $item->getSubtotal();
                    $totalPrice += $item->getTotal();
                }

                //get all product Taxons
                $productTaxons = [];
                foreach($product->getProductTaxons() as $prodTaxon)
                {
                    $productTaxons[] = $prodTaxon->getTaxon()->getCode();
                }

                // test si une règle de promo "cadeau offert existe"
                $promotions = $this->entityManager->getRepository(Promotion::class)->findAll();
                foreach($promotions as $promo)
                {
                    $valid = false;
                    $rules = $promo->getRules();
                    foreach($rules as $rule)
                    {
                        $confRule = $rule->getConfiguration();
                        //error_log(print_r($confRule, true));
                        switch($rule->getType())
                        {
                            case 'contains_product':
                                if($confRule['product_code'] == $productCode) $valid = true;
                                break;

                            case 'has_taxon':
                                foreach($confRule['taxons'] as $taxonCode)
                                {
                                    if(in_array($taxonCode, $productTaxons)) $valid = true;
                                }
                            break;

                            case 'item_total':
                                if(isset($confRule[$channelCode]) && isset($confRule[$channelCode]['amount']))
                                {
                                    if($totalPrice >= $confRule[$channelCode]['amount']) 
                                    {
                                        $valid = true;
                                        $orderItemId = null;
                                    }
                                }
                            break;
                        }
                    }
                    if($valid)
                    {
                        $prodRepo = $this->entityManager->getRepository(Product::class);
                        $actions = $promo->getActions();
                        foreach($actions as $action)
                        {
                            $confAct = $action->getConfiguration();
                            switch($action->getType())
                            {
                                case 'gift_product_discount':
                                    if(isset($confAct['product_code']))
                                    {
                                        // ajouter au panier
                                        if($_prod = $prodRepo->findOneByCode($confAct['product_code']))
                                        {
                                            $_variant = $_prod->getVariants()->first();

                                            //test si variant pas déjà dans le panier!
                                            if(!in_array($_variant->getCode(), $allItemVariantCodes))
                                            {
                                                $_variant->setShippingRequired(false); // utile ?

                                                $giftItem = $this->orderItemFactory->createNew();
                                                $giftItem->setVariant($_variant);

                                                $further = [
                                                    'promotion' => $promo->getId(),
                                                    'gift' => true
                                                ];
                                                if($orderItemId)
                                                {
                                                    $further['linked_to_item'] = $orderItemId;
                                                }
                                                $giftItem->setFurther($further);

                                                //$amount = $_variant->getChannelPricingForChannel($channel)->getPrice();
                                                $amount = 0;
                                                $giftItem->setUnitPrice($amount);
                                                
                                                $this->orderItemQuantityModifier->modify($giftItem, 1);
                                                $order->addItem($giftItem);
                                                $this->entityManager->persist($order);

                                                /*foreach($giftItem->getUnits() as $unit) 
                                                {
                                                    $adjustment = $this->adjustmentFactory->createNew();
                                                    $adjustment->setType(AdjustmentInterface::ORDER_UNIT_PROMOTION_ADJUSTMENT);
                                                    $adjustment->setLabel($promo->getName());
                                                    $adjustment->setOriginCode($promo->getCode());
                                                    $adjustment->setAmount(-$amount);
                                                    $unit->addAdjustment($adjustment);
                                                }*/
                                                $this->entityManager->flush();
                                            }
                                        }
                                    }
                                break;
                            }
                        }
                    }
                }
            }
        }
        $event->setResponse(new JsonResponse($data));
    }

    /**
     * Suppression d'un item du panier
     */
    public function onSyliusOrderItemRemove(GenericEvent $event)
    {
        $orderItem = $event->getSubject();
        $order = $orderItem->getOrder();
        $orderItemId = $orderItem->getId();
        foreach($order->getItems() as $item)
        {
            // suppression eventuel d'un item gratuit s'il est lié à l'item retiré
            $further = $item->getFurther();
            if($further && isset($further['gift']))
            {
                if(isset($further['linked_to_item']) && ($further['linked_to_item'] == $orderItemId))
                {
                    $order->removeItem($item);
                }
                else
                {
                    unset($further['gift']);
                    $item->setFurther($further);
                }
            }
        }
        $this->entityManager->persist($order);
        $this->entityManager->flush();
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

		if($order->overQuantities())
        {
            $this->session->getFlashBag()->add('error', 'Les quantités demandées ne sont pas disponibles, merci de rectifier votre panier.');
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
            $nextOrderState = OrderCheckoutTransitions::TRANSITION_SELECT_PAYMENT;

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
                        //$nextOrderState = OrderCheckoutTransitions::TRANSITION_ADDRESS;
                        $nextOrderState = false;
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

            // recalcul des frais de port
            $this->orderProcessor->process($order);

            // fidelity
            $usedchullpoints = $this->session->get('usedchullpoints');
            if($usedchullpoints && (abs($usedchullpoints) > 0))
            {
                error_log("la reduct : ".$usedchullpoints);
                $order = $event->getSubject();
                $codeName = 'chk_chullpoints';

                // en principe, la réduction n'existe déjà plus!
                $found = false;
                foreach($order->getAdjustments() as $adjustement)
                {
                    if($adjustement->getOriginCode() == $codeName)
                    {
                        //$order->removeAdjustment($adjustement);
                        $found = true;
                    }
                }

                // si on en a définie une, mais qu'elle n'est plus trouvée...
                if(!$found)
                {
                    // ...on va la recréer
                    $chullz = 0; //nbr de points
                    if($customer = $order->getCustomer())
                    {
                        $chullz = $customer->getChullpoints(); //nbr de points sur le site
                    }
                    error_log("chullz : $chullz");
                    $nbrReduc = (int)floor($chullz / 500); // 500 points = 1 bon
                    $discountAmount = $nbrReduc * 10; // 1 bon = 10€
                    $amount = -100 * (int) $discountAmount;
                    $adjustment = $this->adjustmentFactory->createWithData(
                        AdjustmentInterface::ORDER_PROMOTION_ADJUSTMENT,
                        $codeName,
                        $amount
                    );
                    $adjustment->setOriginCode($codeName);
                    $order->addAdjustment($adjustment);
                }
            }

            // changer le state
            if($nextOrderState)
            {
                $stateMachine = $this->stateMachineFactory->get($order, OrderCheckoutTransitions::GRAPH);
                $stateMachine->apply($nextOrderState);
            }
            
            $this->entityManager->flush();
        }
    }

    /**
     * Genère le XML de vente quand le Order est complété
     */
    public function onSyliusOrderPostComplete(GenericEvent $event)
    {
        $order = $event->getSubject();

        // test if C&C
        $inStore = false;
        if($order->hasShipments())
        {
            $shipment = $order->getShipments()->first();
            $shipping_method = $shipment->getMethod()->getCode();
            if($shipping_method == 'store')
            {
                $inStore = true;
                $further = $order->getFurther();
                if($further && isset($further['store']) && !empty($further['store']))
                {
                    $store = $this->entityManager->getRepository(Store::class)->find($further['store']);
                    $inStore = $store->isWarehouse() ? false : $store;
                }
            }
        }
        if($inStore == false)
        {
            $this->izyproHelper->export($order);
            //$this->ginkoiaHelper->export($order);// à faire au retour d'Izypro
        }
        else
        {
            // envoi d'un email au magasin
            if($email = $inStore->getEmail())
            {
                $emails = explode(',', $email);//sépare les emails
                $emails = array_map('trim', $emails);//retire les éventuels espaces
                $this->emailSender->send('click_and_collect', $emails, ['order' => $order, 'store' => $inStore]);
            }
        }
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
            $path = 'media/brand/logos';
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
            $path = 'media/brand/backgrounds';
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
            $path = 'media/brand/backgrounds';
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
            $path = 'media/chullis';
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
            $path = 'media/store/backgrounds';
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
            $path = 'media/store/services';
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
}
