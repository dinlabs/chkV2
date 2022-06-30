<?php

namespace App\EventSubscriber;

use App\Entity\Addressing\Address;
use App\Entity\Chullanka\HistoricOrder;
use App\Service\GinkoiaCustomerWs;
use Doctrine\ORM\EntityManagerInterface;
use Sylius\Bundle\ResourceBundle\Event\ResourceControllerEvent;
use Sylius\Component\Mailer\Sender\SenderInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\SecurityEvents;

class CustomerEventSubscriber implements EventSubscriberInterface
{
    private $entityManager;
    private $emailSender;
    private $ginkoiaCustomerWs;
    protected $addressFactory;

    public function __construct(EntityManagerInterface $entityManager, SenderInterface $emailSender, GinkoiaCustomerWs $ginkoiaCustomerWs, FactoryInterface $addressFactory)
    {
        $this->entityManager = $entityManager;
        $this->emailSender = $emailSender;
        $this->ginkoiaCustomerWs = $ginkoiaCustomerWs;
        $this->addressFactory = $addressFactory;
    }

    public static function getSubscribedEvents()
    {
        return [
            //'security.interactive_login' => 'onSecurityInteractiveLogin',
            SecurityEvents::INTERACTIVE_LOGIN => 'onSecurityInteractiveLogin',

            'sylius.customer.show' => 'onCustomerDashboardShow',

            // call Ginkoia WS
            'sylius.customer.post_register' => 'onSyliusCustomerPostCreate',
            'sylius.customer.post_update' => 'onSyliusCustomerPostSave',
            'sylius.address.post_register' => 'onSyliusCustomerPostSave',
            'sylius.address.post_update' => 'onSyliusCustomerPostSave',

            'app.rma.pre_update' => 'onAppRmaPreUpdate',
        ];
    }

    /**
     * Lors de la connexion du User
     */
    public function onSecurityInteractiveLogin(InteractiveLoginEvent $event)
    {
        $connectedUser = $event->getAuthenticationToken()->getUser();
        if($connectedUser && method_exists($connectedUser, 'getCustomer') && ($customer = $connectedUser->getCustomer()))
        {
            $webserv = $this->ginkoiaCustomerWs;
            $email = $customer->getEmail();

            // On interroge le WS pour mettre à jour les infos du client sur le site
            if($return = $webserv->getCustomerInfos($email))
            {
                if(isset($return['ResultCode']))
                {
                    if($return['ResultCode'] == '-5')
                    {
                        throw new CustomUserMessageAuthenticationException("Comptes clients multiples pour cet email, merci de nous contacter par email à contact@chullanka.com ou par téléphone au 04 89 03 22 75 (du lundi au vendredi, de 9h30 à 13h et de 14h à 18H00, prix d'un appel local)");
                    }
                    if($return['ResultCode'] == '-6')
                    {
                        throw new CustomUserMessageAuthenticationException("Compte client inconnu ou supprimé, veuillez en créer un nouveau. En cas de problème, n'hésitez pas à nous contacter par email à contact@chullanka.com ou par téléphone au 04 89 03 22 75 (du lundi au vendredi, de 9h30 à 13h et de 14h à 18H00, prix d'un appel local)");
                    }
                    return false;
                }
                elseif(isset($return['ID']))
                {
                    $_user = $return;
                    if(isset($_user['Nom']) && ($nom = trim($_user['Nom'])) && !empty($nom))
                    {
                        $customer->setLastname($nom);
                    }
                    if(isset($_user['Prenom']) && ($prenom = trim($_user['Prenom'])) && !empty($prenom))
                    {
                        $customer->setFirstname($prenom);
                    }
                    if(isset($_user['DateAnniversaire']) && ($dob = trim($_user['DateAnniversaire'])) && !empty($dob))
                    {
                        //$aDob = explode('\/', $dob);
                        //$dob = new \Datetime($aDob[2] . '-' . $aDob[1] . '-' . $aDob[0]);

                        $dob = str_replace('//','|', $dob);
                        $dob = str_replace('/','|', $dob);
                        $aDob = explode('|', $dob);
                        //$dob = new \Datetime(implode('-', [ $aDob[2], $aDob[1], $aDob[0] ] ));
                        $dob = new \Datetime(implode('-', array_reverse($aDob)));
                        $customer->setBirthday($dob);
                    }

                    if(isset($_user['Adresse']))
                    {
                        $defaultAddress = $customer->getDefaultAddress();
                        if(!$defaultAddress)
                        {
                            /** @var AddressInterface $address */
                            $defaultAddress = $this->addressFactory->createNew();
                            $customer->setDefaultAddress($defaultAddress);
                        }

                        $defaultAddress->setFirstName( $customer->getFirstname() );
                        $defaultAddress->setLastName( $customer->getLastname() );

                        if(isset($_user['Adresse']['Mobile']) && ($value = trim($_user['Adresse']['Mobile'])) && !empty($value))
                        {
                            $customer->setPhoneNumber($value);
                        }
                        if(isset($_user['Adresse']['Telephone']) && ($value = trim($_user['Adresse']['Telephone'])) && !empty($value))
                        {
                            $defaultAddress->setPhoneNumber($value);
                        }
                        if(isset($_user['Adresse']['Ligne']) && ($value = trim($_user['Adresse']['Ligne'])) && !empty($value))
                        {
                            $value = is_array($value) ? implode("\n", $value) : $value;
                            $defaultAddress->setStreet($value);
                        }
                        else $defaultAddress->setStreet('-');

                        if(isset($_user['Adresse']['Code']) && ($value = trim($_user['Adresse']['Code'])) && !empty($value))
                        {
                            $value = (strlen($value) < 5) ? '0' . $value : $value;
                            $defaultAddress->setPostcode($value);
                        }
                        else $defaultAddress->setPostcode('-');

                        if(isset($_user['Adresse']['Ville']) && ($value = trim($_user['Adresse']['Ville'])) && !empty($value))
                        {
                            $defaultAddress->setCity($value);
                        }
                        else $defaultAddress->setCity('-');

                        if(isset($_user['Adresse']['CodePays']) && ($value = trim($_user['Adresse']['CodePays'])) && !empty($value) && ($value != '-'))
                        {
                            $defaultAddress->setCountryCode($value);
                        }
                        else $defaultAddress->setCountryCode('FR');
                    }

                    // fidélité
                    if(($loyalties = $webserv->getCustomerLoyalties($email)) && isset($loyalties['loyalty_total_points']))
                    {
                        $chullz = $loyalties['loyalty_total_points'];// on récupère le nbre de points à jour sur le WS
                        $customer->setChullpoints($chullz);
                    }

                    $this->entityManager->persist($customer);

                    
                    // On interroge le WS pour récupérer ses commandes en magasin via son email
                    if($orders = $webserv->getCustomerShopOrders($email))
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
                                $_date = new \Datetime( implode('-', array_reverse($_tmp)));// récupération au format ==> 2021-06-01
        
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
                    }
                }
                $this->entityManager->flush();
            }
        }
    }

    public function onCustomerDashboardShow(ResourceControllerEvent $event)
    {
        $customer = $event->getSubject();
        if($customer->getNotice() > 0)
        {
            $customer->setNotice(0);
            $this->entityManager->persist($customer);
            $this->entityManager->flush();
        }
    }

    /**
     * Création de compte - ajout notification
     */
    public function onSyliusCustomerPostCreate(ResourceControllerEvent $event)
    {
        $customer = $event->getSubject();
        $customer->setNotice(1);
        $this->entityManager->persist($customer);
        $this->entityManager->flush();

        $this->onSyliusCustomerPostSave($event);
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
        
        // default Address
        $defaultAddress = $customer->getDefaultAddress();
        if(!$defaultAddress)
        {
            if($customerAddresses = $customer->getAddresses())
            {
                $defaultAddress = $customerAddresses->first();
            }
        }

        // Billing
        if($defaultAddress)
        {
            $billingAddress = $defaultAddress;
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
        }
        
        
        // Shipping
        if($defaultAddress)
        {
            $shippingAddress = $defaultAddress;
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
        }
        

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

    
    /**
     * Màj du statut RMA
     */
    public function onAppRmaPreUpdate(ResourceControllerEvent $event)
    {
        $rma = $event->getSubject();

        //'Retour produit accepté'
        if($rma->getState() == 'product_return_accepted')
        {
            $rma->setReturnSlip(true);
        }

        // send email
        $this->emailSender->send('rma_change_state', [$rma->getCustomer()->getEmail()], ['rma' => $rma]);
    }
}
