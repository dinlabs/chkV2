<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Chullanka\Store;
use App\Entity\Order\Order;
use App\Entity\Order\OrderItem;
use App\Entity\Product\ProductVariant;
use App\Entity\Shipping\Shipment;
use App\Service\SFTPConnection as ServiceSFTPConnection;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use SFTPConnection;
use SM\Factory\FactoryInterface;
use Sylius\Bundle\ApiBundle\Command\SendShipmentConfirmationEmail;
use Sylius\Component\Core\Model\AdjustmentInterface;
use Sylius\Component\Core\OrderShippingTransitions;
use Sylius\Component\Shipping\ShipmentTransitions;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DispatchAfterCurrentBusStamp;

class IzyproHelper
{
    private $entityManager;
    private $stateMachineFactory;
    private $eventBus;
    private $projectDir;
    private $tmpDir;
    private $exportDir;
    private $channel;
    private $doc;
    private $commentaires;
    private $urlTracking = '';
    private $chronorelais = false;
    private $dpdfrrelais = false;
    private $totaux;

    public function __construct(EntityManagerInterface $entityManager, FactoryInterface $stateMachineFactory, MessageBusInterface $eventBus, string $projectDir)
    {
        $this->entityManager = $entityManager;
        $this->stateMachineFactory = $stateMachineFactory;
        $this->eventBus = $eventBus;
        $this->projectDir = $projectDir;
        $this->tmpDir = $this->projectDir . '/var/tmp/izypro/';
        $this->exportDir = $this->projectDir . '/var/exports/';
        $this->totaux = [];
    }
    private function chkParameter($slug)
    {
        return $this->entityManager->getRepository(Parameter::class)->getValue($slug);
    }

    /**
     * 
     */
    public function export(Order $order)
    {
        //todo ?
        //if($order->getCheckoutState() == 'completed')

        $order_id = $order->getId();

        // création du XML
        $this->doc = $this->xmlOrder($order);
        
        $filename = 'PRZ_CC_IN_10_' . $order->getCheckoutCompletedAt()->format('YmdHis') . '_' . $order_id . '.xml';
        $file = $this->exportDir . $filename;
        if(file_exists($file)) { unlink ($file); }

        if(file_put_contents($file, $this->doc->saveXML(), FILE_APPEND))
        {
            //todo: envoyer le XML par FTP

            return  "XML bien généré : ".$filename;
        }
        else return "Pb pour générer le fichier $filename";
    }


    /**
     * Change le shipment_state && order_shipping_state
     */
    public function changeOrderInStoreState($id, $transition)
    {
        if(!empty($id) && !empty($transition))
        {
            if($shipment = $this->entityManager->getRepository(Shipment::class)->find($id))
            {
                $stateMachine = $this->stateMachineFactory->get($shipment, ShipmentTransitions::GRAPH);
                if($stateMachine->can($transition)) 
                {
                    $stateMachine->apply($transition);
                }

                $order = $shipment->getOrder();
                $stateMachine = $this->stateMachineFactory->get($order, OrderShippingTransitions::GRAPH);
                if($stateMachine->can($transition)) 
                {
                    $stateMachine->apply($transition);
                }

                $this->entityManager->flush();
            }
        }
    }

    public function updateOrderStates()
    {
        //return 
        $this->doSftp('status');
        //$this->treatFiles(); // traitement sur le serveur du site
    }

    /**
     * Use SFTP connection to get or send files
     */
    private function doSftp($TRtype, $file = '')
    {
        /* SET VARIABLES */
        $ftp_server = $this->chkParameter('izypro-sftp-host');//'172.16.120.44'
        $ftp_port = $this->chkParameter('izypro-sftp-port');//'222';
        $user = $this->chkParameter('izypro-sftp-user');//'ftpuser';
        $passwd = $this->chkParameter('izypro-sftp-pass');//'XqUSRSgv88jkxpvPINNw';
        $importDir = $this->chkParameter('izypro-import-directory');//'/WMS_TO_MGNT/';
        $exportDir = $this->chkParameter('izypro-export-directory');//'/MGNT_TO_WMS/';
        
        if(empty($ftp_server) || empty($user) || empty($passwd))
            return false;
        
        try
        {
            $sftp = new ServiceSFTPConnection($ftp_server, $ftp_port);
            $sftp->login($user, $passwd);
            
            if($TRtype == 'sendxml')
            {
                $tmpFile = basename($file);
                $remote_file = $exportDir . $tmpFile;
                if($sftp->uploadFile($file, $remote_file))
                {
                    error_log('SFTP :: fichier '.$file.' écrit avec succès');
                    //$this->reportMsg[] = 'SFTP :: fichier '.$file.' écrit avec succès';
                    // We move the tmp file in a logfiles dir
                    if(!rename($file, $this->logfilesDir . DIRECTORY_SEPARATOR . basename($file)))
                    {
                        error_log('SFTP :: fichier '.$file.' non déplacé dans izyprofiles');
                        //$this->reportMsg[] = 'SFTP :: fichier '.$file.' non déplacé dans izyprofiles';
                    }
                }
            }
            else
            {
                $files = $sftp->scanFilesystem($importDir);// liste des fichiers
                sort($files);

                // test 
                echo "<pre>";
                print_r($files);
                echo "</pre>";
                return;
                // test 


                if(count($files)>0)
                {
                    // PROCESS FILE BY FILE
                    for($i = 0; isset($files[$i]); $i++)
                    {
                        if(strpos($files[$i], 'ARCHIVE') > -1) continue;
                        
                        error_log('SFTP :: Fichier courant : '.$files[$i]);
                        //$this->reportMsg[] = 'SFTP :: Fichier courant : '.$files[$i];
                        
                        if(file_exists($this->logfilesDir . '/' . basename($files[$i])))
                        {
                            // If process was ok, delete the distant file
                            if($sftp->deleteFile($importDir . '/' . $files[$i]))
                            {
                                error_log('SFTP :: fichier distant '.$files[$i].' supprimé du serveur');
                                //$this->reportMsg[] = 'SFTP :: fichier distant '.$files[$i].' supprimé du serveur';
                            }
                            else
                            {
                                error_log('SFTP :: ERREUR : le fichier '.$files[$i].' n\'a pu être supprimé du serveur distant');
                                //$this->reportMsg[] = 'SFTP :: ERREUR : le fichier '.$files[$i].' n\'a pu être supprimé du serveur distant';
                            }
                        }
                        else
                        {
                            $tmpFile = $this->tmpDir . '/' . basename($files[$i]);
                            if($sftp->receiveFile($importDir . '/' . $files[$i], $tmpFile))
                            {
                                error_log('SFTP :: fichier '.$files[$i].' téléchargé');
                                //$this->reportMsg[] = 'SFTP :: fichier '.$files[$i].' téléchargé';
                            }
                            else
                            {
                                error_log('SFTP :: le fichier '.$files[$i].' n\'a pas été téléchargé dans '.$tmpFile);
                                //$this->reportMsg[] = 'SFTP :: le fichier '.$files[$i].' n\'a pas été téléchargé dans '.$tmpFile;
                            }
                        }
                    }
                }
            }
        }
        catch (\Exception $e)
        {
            echo $e->getMessage() . "\n";
            error_log('SFTP :: Erreur : '.$e->getMessage());
            //$this->reportMsg[] = 'SFTP :: Erreur : '.$e->getMessage();
        }
    }

    /**
     * Treat files already downloaded
     */
    private function treatFiles() 
    {        
        $files = scandir($this->tmpDir); // liste des fichiers dans le rep. temporaire
        sort($files);
        if(!count($files))
        {
            error_log('Izypro :: Aucun fichier dans '.$this->tmpDir);
            //$this->reportMsg[] = 'Izypro :: Aucun fichier dans '.$this->tmpDir;
        }
        
        // PROCESS FILE BY FILE
        for($i = 0; isset($files[$i]); $i++)
        {
            if(($files[$i] == '.') || ($files[$i] == '..'))
                continue;
            
            $tmpFile = $this->tmpDir . '/' . basename($files[$i]);
            $prefix = strtoupper(substr(basename($files[$i]), 0, 6));
            
            if($prefix == 'PRZ_CC')
            {
                $this->doSftp('sendxml', $tmpFile);
                continue;
            }
            
            // Read file and update db, then close file
            $fp = fopen($tmpFile, 'r');
            while(!feof($fp))
            {
                $line = fgets($fp, 4096);
                if(strlen($line) && ($prefix != 'IS_PRZ'))
                    $return = $this->statusUpdateFunc($line);
            }
            fclose($fp);

            /*
            if($return == true)
            {
                // We move the tmp file in a logfiles dir
                if(!rename($tmpFile, $this->logfilesDir . '/' . basename($files[$i])))
                {
                    error_log('Izypro :: ERREUR : le fichier '.$files[$i].' n\'a pu être déplacé dans izyprofiles');
                    //$this->reportMsg[] = 'Izypro :: ERREUR : le fichier '.$files[$i].' n\'a pu être déplacé dans izyprofiles';
                }
            }
            else
            {
                error_log('Izypro :: le fichier '.$files[$i].' n\'a pas été traité correctement');
                //$this->reportMsg[] = 'Izypro :: le fichier '.$files[$i].' n\'a pas été traité correctement';
            }
            */
        }
    }

    private function statusUpdateFunc($line)
    {
        $data = explode(';', $line);

        $number = (count($data)>1) ? $data[1] : '';
        if(!empty($number))
        {
            //get Order
            if(($order = $this->entityManager->getRepository(Order::class)->findOneByNumber($number)) && $order->hasShipments())
            {
                $shipment = $order->getShipments()->first();
                $shipmentId = $shipment->getId();
                
                $statusCol = $data[2];
                switch($statusCol)
                {
                    case 1:
                        $this->changeOrderInStoreState($shipmentId, 'stock_trouble');
                        break;

                    case 7:
                        $this->changeOrderInStoreState($shipmentId, 'in_preparation');
                        break;
                    
                    case 8:
                        $this->changeOrderInStoreState($shipmentId, 'before_ship');
                        break;
                        
                    case 9:
                        $this->changeOrderInStoreState($shipmentId, 'ship');
                        
                        //$shipment->addComment($comment, true, true);
                        if(!empty($trackNum = $data[3]))
                        {
                            $shipment->setTracking($trackNum);
                            $this->entityManager->flush();
                        }

                        // email de confirmation d'envoi
                        $this->eventBus->dispatch(new SendShipmentConfirmationEmail($shipmentId), [new DispatchAfterCurrentBusStamp()]);
                        break;
                        
                        case 2:
                        case 120:
                        case 150:
                        case 200:
                            break;
                    }
                    
                return true;
            }           
        }
        return;
    }
    
    /**
     * Generate XML node of an order
     * @param Order $order
     * @return DOMDocument
     */
    private function xmlOrder(Order $order): \DOMDocument
    {
        $this->channel = $order->getChannel();
        $order_id = $order->getId();
        $customer = $order->getCustomer();
        $orderedItems = $order->getItems();
        $commandeDate = $order->getCheckoutCompletedAt()->format('Y-m-d H:i:s');
        $totalPrice = round($order->getTotal()/100, 2);

        // XML
        $this->doc = new \DOMDocument('1.0', 'UTF-8');
        $this->doc->formatOutput = true;
        $root = $this->doc->createElement('commandes');
        $root->setAttribute('xmlns:od', 'http://www.w3.org/2001/XMLSchema-instance');
        $this->doc->appendChild($root);
    
        $entete = $this->doc->createElement('CC_ENTETE_10');
        $root->appendChild($entete);
    
        $entete->appendChild($this->addKeyVal('DEPOSANT', 'PRZ'));
        $entete->appendChild($this->addKeyVal('SHOP', 'CHK'));
        $entete->appendChild($this->addKeyVal('SITE', '0'));
    
        $entete->appendChild($this->addKeyVal('COMMANDE', $order_id));
        $entete->appendChild($this->addKeyVal('COMMANDE_CLIENT', $order->getNumber()));
        $entete->appendChild($this->addKeyVal('COMMANDE_N3', $order->getNumber()));
        //$entete->appendChild($this->addKeyVal('COMMANDE_N3', 'CHK'.$order_id));
        $entete->appendChild($this->addKeyVal('TYPECDE', 'CHK'));
        $entete->appendChild($this->addKeyVal('NBLIG_N3', count($orderedItems)));
        $entete->appendChild($this->addKeyVal('HCREATN3_CDE', $commandeDate)); // Facultatif


        // PRODUCTS
        $productLines = [];
        $taxonIds = [];
        foreach($orderedItems as $item)
        {
            $variant = $item->getVariant();

            if($further = $item->getFurther())
            {
                if(isset($further['pack']) && !empty($further['pack']))
                {
                    // récupération des produits du pack
                    foreach($further['pack'] as $ppvid => $unitPrice)
                    {
                        $ppVariant = $this->entityManager->getRepository(ProductVariant::class)->find($ppvid);
                        if($ligneNode = $this->getItemNode($item, $unitPrice, $item->getQuantity(), $ppVariant, $order_id))
                        {
                            $entete->appendChild($ligneNode);
                        }
                    }
                    continue;//on ne prend pas en compte les infos du pack lui-même
                }
            }

            if($ligneNode = $this->getItemNode($item, $item->getUnitPrice(), $item->getQuantity(), $variant, $order_id))
            {
                $productLines[] = $ligneNode;
            }

            // test les catégories
            $product = $variant->getProduct();

            $taxonIds[] = $product->getMainTaxon()->getId();
            foreach($product->getProductTaxons() as $prodTaxon)
            {
                $taxonIds[] = $prodTaxon->getTaxon()->getId();
            }
        }
        $taxonIds = array_unique($taxonIds);
        

        /*$gift_message_id = $order->getGiftMessageId();
        if(!is_null($gift_message_id))
        {
            $entete->appendChild($this->addKeyVal('CADEAU', '1')); // Facultatif

            $message = Mage::getModel('giftmessage/message');
            $message->load((int)$gift_message_id);
            //$gift_sender = $message->getData('sender');
            //$gift_recipient = $message->getData('recipient');

            $entete->appendChild($this->addKeyVal('CADEAU_MSG', $message->getData('message'), true)); // Facultatif
        }
        else
        {
            $entete->appendChild($this->addKeyVal('CADEAU', '0')); // Facultatif
            $entete->appendChild($this->addKeyVal('CADEAU_MSG', '')); // Facultatif
        }*/

        $entete->appendChild($this->addKeyVal('ALERT_CLT', '0')); // Facultatif
        $entete->appendChild($this->addKeyVal('TOTAL_TTC', $totalPrice)); // Facultatif

        //$payment_method = $order->getPayment()->getMethodInstance()->getTitle();
        $payment_method = '';
        $entete->appendChild($this->addKeyVal('MODE_PAIEMENT', $payment_method)); // Facultatif
        $entete->appendChild($this->addKeyVal('DPREVUE_CDE', '')); // Facultatif
        $entete->appendChild($this->addKeyVal('DLIV_CDE', '')); // Facultatif
        $entete->appendChild($this->addKeyVal('INFOENTLIV1', '')); // Facultatif
        $entete->appendChild($this->addKeyVal('INFOENTLIV2', '')); // Facultatif
        $entete->appendChild($this->addKeyVal('INFOENTLIV3', '')); // Facultatif
        $entete->appendChild($this->addKeyVal('MODELIV', '')); // Facultatif
        $entete->appendChild($this->addKeyVal('INCOTERM', '')); // Facultatif


        // Livraison
        $shipAddr = $order->getShippingAddress();
        $shipCountry = $shipAddr->getCountryCode();
        $entete->appendChild($this->addKeyVal('CODECLIENT_LIV', $shipAddr->getId())); // Facultatif
        //$entete->appendChild($this->addKeyVal('CODE_LANGUE_LIV', ''); // Facultatif
        $entete->appendChild($this->addKeyVal('NOMCLIENT_LIV', $shipAddr->getLastname()));
        $entete->appendChild($this->addKeyVal('PRENOMCLIENT_LIV', $shipAddr->getFirstname()));
        $entete->appendChild($this->addKeyVal('SOCIETECLIENT_LIV', $shipAddr->getCompany())); // Facultatif
        $shipStreet = explode('\n', $shipAddr->getStreet());
        $entete->appendChild($this->addKeyVal('ADRESSE1_LIV', $shipStreet[0]));
        $ligneAdresse2 = isset($shipStreet[1]) ? $shipStreet[1] : '';
        $entete->appendChild($this->addKeyVal('ADRESSE2_LIV', $ligneAdresse2)); // Facultatif
        $ligneAdresse3 = isset($shipStreet[2]) ? $shipStreet[2] : '';
        $entete->appendChild($this->addKeyVal('ADRESSE3_LIV', $ligneAdresse3)); // Facultatif
        $entete->appendChild($this->addKeyVal('AUTRES_INFO_LIV', '')); // Facultatif
        $entete->appendChild($this->addKeyVal('CODEPOSTAL_LIV', $shipAddr->getPostcode()));
        $entete->appendChild($this->addKeyVal('VILLE_LIV', $shipAddr->getCity()));
        $entete->appendChild($this->addKeyVal('CODEPAYS_LIV', $shipCountry));
        $entete->appendChild($this->addKeyVal('PAYS_LIV', $shipCountry)); // Facultatif
        $entete->appendChild($this->addKeyVal('CONTACT_LIV', '')); // Facultatif
        $entete->appendChild($this->addKeyVal('TEL_LIV', $shipAddr->getPhoneNumber()));
        //$entete->appendChild($this->addKeyVal('FAX_LIV', '')); //Facultatif
        $entete->appendChild($this->addKeyVal('MOBILE_LIV', $customer->getPhoneNumber()));
        $entete->appendChild($this->addKeyVal('EMAIL_LIV', $customer->getEmail())); //Facultatif


        // Transporteur
        $code_carrier = $codeRelais = $codeReseauRelais = '';
        if($order->hasShipments())
        {
            $codeRelais = $store = '';
            $shipment = $order->getShipments()->first();

            $shipping_method = $shipment->getMethod()->getCode();
            $split_ship = explode('_', $shipping_method);
            $shipping_method_type = $split_ship[0];
            
            if($shipping_method_type == 'home')
            {
                $code_carrier = ($split_ship[1] == 'express') ? '020' : '001';

                // test si AMS et > 200€
                if($order->getItemsTotal() > 24900)
                {
                    // AMS = electro 
                    $checkTaxonIds = [
                        72,//Montres trail running
                        73,//Montres GPS et altimetre
                        74,//Cameras, télépones et accessoires 
                        75,//GPS de randonnée
                        76,//Orientation
                        77,//Electrostimulation
                    ];
                    $arrayIntersect = array_intersect($taxonIds, $checkTaxonIds);
                    if(count($arrayIntersect)) $code_carrier = '002';//colissimo signature

                    if($shipCountry != 'FR') $code_carrier = '004';//colissimo étranger
                }
            }
            elseif($shipping_method_type == 'pickup')
            {
                $code_carrier = ($split_ship[1] == 'express') ? '021' : '082';

                $further = $order->getFurther();
                if($further && isset($further['pickup_id']) && !empty($further['pickup_id']))
                {
                    $codeRelais = $further['pickup_id'];
                }
            }
            elseif($shipping_method == 'store')
            {
                $shipping_method = 'Retrait en magasin';
                $further = $order->getFurther();
                if($further && isset($further['store']) && !empty($further['store']))
                {
                    $store = $this->entityManager->getRepository(Store::class)->find($further['store']);
                    $store = $store->getName();
                }

                $code_carrier = '010';
            }
            else
            {
                $shipping_method = $shipment->getMethod()->getName();
            }
        }
        $entete->appendChild($this->addKeyVal('TYPE_TRANS', $code_carrier));
        $entete->appendChild($this->addKeyVal('POIDS_CHOIX_TPT', ''));

        $entete->appendChild($this->addKeyVal('CODETRANS', '')); // Facultatif
        $entete->appendChild($this->addKeyVal('NOMTRANS', '')); // Facultatif

        
        // Relais ?
        $entete->appendChild($this->addKeyVal('CODE_PT_RELAIS', $codeRelais));
        $entete->appendChild($this->addKeyVal('CODE_RESEAU_RELAIS', $codeReseauRelais));


        // Facturation
        $billAddr = $order->getBillingAddress();
        $billCountry = $billAddr->getCountryCode();

        $entete->appendChild($this->addKeyVal('CODECLIENT_FACT', $billAddr->getId())); //Facultatif
        $entete->appendChild($this->addKeyVal('CODE_LANGUE_FACT', 'fr')); //Facultatif
        $entete->appendChild($this->addKeyVal('NOMCLIENT_FACT', $billAddr->getLastname())); //Facultatif
        $entete->appendChild($this->addKeyVal('PRENOMCLIENT_FACT', $billAddr->getFirstname())); //Facultatif
        $entete->appendChild($this->addKeyVal('SOCIETECLIENT_FACT', $billAddr->getCompany())); //Facultatif
        $billStreet = explode('\n', $billAddr->getStreet());
        $entete->appendChild($this->addKeyVal('ADRESSE1_FACT', $billStreet[0])); //Facultatif
        $ligneAdresse2 = isset($billStreet[1]) ? $billStreet[1] : '';
        $entete->appendChild($this->addKeyVal('ADRESSE2_FACT', $ligneAdresse2)); //Facultatif
        $ligneAdresse3 = isset($billStreet[2]) ? $billStreet[2] : '';
        $entete->appendChild($this->addKeyVal('ADRESSE3_FACT', $ligneAdresse3)); //Facultatif
        $entete->appendChild($this->addKeyVal('AUTRES_INFO_FACT', '')); //Facultatif
        $entete->appendChild($this->addKeyVal('CODEPOSTAL_FACT', $billAddr->getPostcode())); //Facultatif
        $entete->appendChild($this->addKeyVal('VILLE_FACT', $billAddr->getCity())); //Facultatif
        $entete->appendChild($this->addKeyVal('CODEPAYS_FACT', $billCountry)); //Facultatif
        $entete->appendChild($this->addKeyVal('PAYS_FACT', $billCountry)); //Facultatif
        $entete->appendChild($this->addKeyVal('CONTACT_FACT', '')); //Facultatif
        $entete->appendChild($this->addKeyVal('TEL_FACT', $billAddr->getPhoneNumber())); //Facultatif
        //$entete->appendChild($this->addKeyVal('FAX_FACT', '')); //Facultatif
        $entete->appendChild($this->addKeyVal('MOBILE_FACT', $customer->getPhoneNumber())); //Facultatif
        $entete->appendChild($this->addKeyVal('EMAIL_FACT', $customer->getEmail())); //Facultatif

        
        $this->commentaires = '';
        
        // Infos Supplémentaires
        $entete->appendChild($this->addKeyVal('COMMENTBP', '')); //Facultatif
        $entete->appendChild($this->addKeyVal('COMMENTBE', '')); //Facultatif
        $entete->appendChild($this->addKeyVal('REDUC_CODE', '')); //Facultatif
        $entete->appendChild($this->addKeyVal('REDUC_TYPE', '')); //Facultatif
        $entete->appendChild($this->addKeyVal('REDUC_VAL', '')); //Facultatif
        $entete->appendChild($this->addKeyVal('REDUC_MSG', '')); //Facultatif
        $entete->appendChild($this->addKeyVal('REDUC_FV', '')); //Facultatif


        foreach($productLines as $ligneNode)
        {
            $entete->appendChild($ligneNode);
        }
        
        // Infos Supplémentaires
        $entete->appendChild($this->addKeyVal('COMMENTBL', $this->commentaires));
        
        return $this->doc;
    }

    /**
     * Return an XML node
     */
    private function addKeyVal(string $key, mixed $val, bool $cdata = false)
    {
        $val = trim((string)$val);
        $node = $this->doc->createElement($key);
        $node->appendChild( $cdata ? $this->doc->createCDATASection($val) : $this->doc->createTextNode($val) );
        return $node;
    }

    /**
     * Return an item XML node
     */
    private function getItemNode(OrderItem $item, int $unitPrice, int $qty, ProductVariant $variant, int $order_id): \DOMElement
    {
        $taxAmount = $variant->getTaxCategory()->getRates()->first()->getAmount();
        $valPUTTC = $unitPrice / 100;//Sylius enregistre les prix en centimes
        $valPUHT = $valPUTTC / (1 + $taxAmount);
        $valPUBrutTTC = $variant->getChannelPricingForChannel($this->channel)->getPrice() / 100;
        $valPUBrutHT = $valPUBrutTTC / (1 + $taxAmount);
        $valTxTva = $taxAmount * 100;
        
        $valPXHT = $valPUHT * $qty;
        $valPXTTC = $valPUTTC * $qty;

        //todo
        // decremente stock web pour produit simple

        
        $prod = $this->doc->createElement('CC_LIGNE_10');

        $prod->appendChild($this->addKeyVal('DEPOSANT', 'PRZ'));
        $prod->appendChild($this->addKeyVal('SHOP', 'CHK'));
        $prod->appendChild($this->addKeyVal('SITE', '0'));
        $prod->appendChild($this->addKeyVal('COMMANDE', $order_id));
        $prod->appendChild($this->addKeyVal('LIG_CDE', $item->getId()));

        /*$product = $item->getProduct();
        $product_id = $product->getEntityId();
        if($item->getProductType() == 'configurable')
            $product_id = $product->getAttributeSetId();*/

        $prod->appendChild($this->addKeyVal('REFERENCE', $variant->getCode()));
        $prod->appendChild($this->addKeyVal('DESIGNATION_LIG', $variant->getName()));
        $prod->appendChild($this->addKeyVal('QTECDEE_LIGCDE', $qty));
        $prod->appendChild($this->addKeyVal('STOCK', ''));
        $prod->appendChild($this->addKeyVal('PUHT_VENTE', number_format($valPUHT, 2, '.', ''))); //Facultatif
        //$prod->appendChild($this->addKeyVal('DEVISE', Mage::app()->getStore()->getBaseCurrencyCode())); //Facultatif
        $prod->appendChild($this->addKeyVal('DEVISE', 'EUR')); //Facultatif
        
        // Options
        $infoligliv1 = $infoligliv2 = '';
        /*$options = $item->getProductOptions();
        if(isSet($options['info_buyRequest']['mounting']) && isSet($options['info_buyRequest']['mount']))
        {
            $montage = $options['info_buyRequest']['mount'];
            $infoligliv1 = $this->formatMountLine($montage);
            
            if($montage['comments'])
            {
                //$infoligliv2 = $montage['comments'];
                $infoligliv2 = str_replace(array("\t", "\r", "\n", "\f", ";", "|"), " ", $montage['comments']);
                $this->commentaires .= ' ' . $infoligliv2;
            }
        }*/
        $prod->appendChild($this->addKeyVal('INFOLIGLIV1', $infoligliv1)); //Facultatif
        $prod->appendChild($this->addKeyVal('INFOLIGLIV2', $infoligliv2)); //Facultatif
        $prod->appendChild($this->addKeyVal('INFOLIGLIV3', '')); //Facultatif
        $prod->appendChild($this->addKeyVal('CODE_TVA', '0')); //Facultatif

        return $prod;
    }

    private function getCarrierCode($shipping_method, $getId = false)
    {
        $carrier_id = $carrier_code = '';
        $_urls = [
                'colissimo' => 'http://www.colissimo.fr/portail_colissimo/suivre.do?colispart=',
                'chrono'    => 'http://www.chronopost.fr/expedier/inputLTNumbersNoJahia.do?lang=fr_FR&listeNumeros=',
                'dpd'       => 'http://www.dpd.fr/traces_info_'
        ];
    
        if(strpos($shipping_method, 'socolissimo_commercant')>-1)
        {
            $carrier_id = '008';
            $carrier_code = 'socolissimo';
            $this->urlTracking = $_urls['colissimo'];
        }
        else if(strpos($shipping_method, 'socolissimo_poste')>-1)
        {
            $carrier_id = '005';
            $carrier_code = 'socolissimo';
            $this->urlTracking = $_urls['colissimo'];
        }
        else if(strpos($shipping_method, 'socolissimo_domicile_etranger')>-1)
        {
            $carrier_id = '004';
            $carrier_code = 'socolissimo';
            $this->urlTracking = $_urls['colissimo'];
        }
    	else if(strpos($shipping_method, 'socolissimo_domicile_domtom')>-1)
    	{
    		$carrier_id = '004';
    		$carrier_code = 'socolissimo';
    		$this->urlTracking = $_urls['colissimo'];
    	}
    	else if(strpos($shipping_method, 'socolissimo')>-1)
        {
            $carrier_id = '001';
            $carrier_code = 'socolissimo';
            $this->urlTracking = $_urls['colissimo'];
        }
    	else if(strpos($shipping_method, 'colissimo_domicile_signature')>-1)
        {
            $carrier_id = '002';
            $carrier_code = 'socolissimo';
            $this->urlTracking = $_urls['colissimo'];
        }
        else if(strpos($shipping_method, 'chronopost')>-1)
        {
            $carrier_id = '020';
            $carrier_code = 'chronopost';
            $this->urlTracking = $_urls['chrono'];
        }
        else if(strpos($shipping_method, 'chronorelais')>-1)
        {
            $carrier_id = '021';
            $carrier_code = 'chronorelais';
            $this->chronorelais = true;
            $this->urlTracking = $_urls['chrono'];
        }
        else if(strpos($shipping_method, 'dpdfrpredict')>-1)
        {
            $carrier_id = '081';
            $carrier_code = 'dpdfrpredict';
            $this->urlTracking = $_urls['dpd'];
        }
        else if(strpos($shipping_method, 'dpdfrrelais')>-1)
        {
            $carrier_id = '082';
            $carrier_code = 'dpdfrrelais';
            $this->dpdfrrelais = true;
            $this->urlTracking = $_urls['dpd'];
        }
        else if(strpos($shipping_method, 'shoppingflux')>-1)
        {
            $carrier_id = '001';
            $carrier_code = 'socolissimo';
            $this->urlTracking = $_urls['colissimo'];
        }
        else if(strpos($shipping_method, 'collectinstore')>-1)
        {
            $carrier_id = '010';
            $carrier_code = 'collectinstore';
        }
                        
        if(empty($carrier_code))
            error_log('Izypro :: manque carrier_code');
    
        return $getId ? $carrier_id : $carrier_code;
    }
}