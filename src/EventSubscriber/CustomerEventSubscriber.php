<?php

namespace App\EventSubscriber;

use App\Entity\Addressing\Address;
use App\Entity\Chullanka\HistoricOrder;
use App\Service\GinkoiaCustomerWs;
use Doctrine\ORM\EntityManagerInterface;
use Sylius\Bundle\ResourceBundle\Event\ResourceControllerEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\SecurityEvents;

class CustomerEventSubscriber implements EventSubscriberInterface
{
    private $entityManager;
    private $ginkoiaCustomerWs;

    public function __construct(EntityManagerInterface $entityManager, GinkoiaCustomerWs $ginkoiaCustomerWs)
    {
        $this->entityManager = $entityManager;
        $this->ginkoiaCustomerWs = $ginkoiaCustomerWs;
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
            // reinit notif
            $customer->setNotice(0);

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
            }
            $this->entityManager->flush();
        }
    }

    public function onCustomerDashboardShow(ResourceControllerEvent $event)
    {
        $customer = $event->getSubject();
        $customer->setNotice(0);
        $this->entityManager->persist($customer);
        $this->entityManager->flush();

        return $this->onSyliusCustomerPostSave($event);
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

        return $this->onSyliusCustomerPostSave($event);
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
