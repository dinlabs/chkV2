<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Chullanka\Store;
use App\Entity\Order\Order;
use App\Entity\Order\OrderItem;
use App\Entity\Product\ProductVariant;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Sylius\Component\Core\Model\AdjustmentInterface;

class GinkoiaHelper
{
    private $entityManager;
    private $projectDir;
    private $exportDir;
    private $channel;
    private $doc;
    private $totaux;

    public function __construct(EntityManagerInterface $entityManager, string $projectDir)
    {
        $this->entityManager = $entityManager;
        $this->projectDir = $projectDir;
        $this->exportDir = $this->projectDir . '/var/exports/';
        $this->totaux = [];
    }

    public function export(Order $order)
    {
        //todo ?
        //if($order->getCheckoutState() == 'completed')

        // création du XML
        $this->doc = $this->xmlOrder($order);
        
        $filename = 'GINK_' . $order->getCheckoutCompletedAt()->format('YmdHis') . '.xml';
        $file = $this->exportDir . $filename;
        if (file_exists($file)) { unlink ($file); }

        if(file_put_contents($file, $this->doc->saveXML(), FILE_APPEND))
        {

            //todo: envoyer le XML par FTP

            return  "XML bien généré : ".$filename;
        }
        else return "Pb pour générer le fichier $filename";
    }
    
    /**
     * Generate XML node of an order
     * @param Order $order
     * @return DOMDocument
     */
    private function xmlOrder(Order $order, int $coef = 1): \DOMDocument
    {
        $this->doc = new \DOMDocument('1.0', 'UTF-8');
        $this->doc->xmlStandalone = true;
        $this->doc->formatOutput = true;
        
        $this->channel = $order->getChannel();
        $realOrderId = $order->getNumber();
        $commandeId = $order->getId();
        $commandeDate = $order->getCheckoutCompletedAt()->format('Y-m-d H:i:s');
        
        // paiement
        if($order->hasPayments())
        {
            $payment = $order->getPayments()->first();
            $codeName = $payment->getMethod()->getCode();
            switch($codeName)
            {
                case 'be2bill':
                case 'atos_standard':
                    $codeName = 'CB';
                    break;
                case 'paypal_express':
                case 'paypal_express_admin':
                    $codeName = 'PayPal';
                    break;
                case 'cb3x':
                    $codeName = 'CB3X';
                    break;
                case 'gift_card_payment':
                    $codeName = 'Carte Cadeau';
                    break;
                case 'iziflux_purchaseorder':
                    $codeName = 'IziFlux';
                    break;
                case 'shoppingflux_purchaseorder':
                    $codeName = 'ShoppingFlux';
                    break;
                case 'ccsave':
                    $codeName = 'ccsave';
                    break;
            }
            
            
            // on prend la premiere facturation pour la date de reference
            //$invoice = $order->getInvoiceCollection()->getFirstItem();
            
            $dateReglement = $payment->getCreatedAt()->format('Y-m-d H:m:s');
        }
        
        // si remboursement
        /*if($coef < 0)
        {
            // on prend le dernier (au cas où il y en aurait eu avant)
            $creditmemo = $order->getCreditmemosCollection()->getLastItem();
            $refundArray = array();
            foreach($creditmemo->getAllItems() as $item)
            {
                //$refundArray[] = $item->getProductId();
                if($item->getPrice() != 0)
                    $refundArray[] = $item->getSku();
            }
            
            $realOrderId .= '-' . $creditmemo->getIncrementId() . '-'. count($refundArray);
            
            $commandeId = $creditmemo->getId();
            $commandeDate = $creditmemo->getCreatedAtStoreDate()->toString('yyyy-MM-dd HH:mm:ss');
            $dateReglement = $creditmemo->getCreatedAtStoreDate()->toString('yyyy-MM-dd HH:mm:ss');
        }*/
        
        // Order
        $orderNode = $this->doc->createElement('Commande');
        $orderNode->appendChild($this->addKeyVal('CommandeNum', $realOrderId));
        $orderNode->appendChild($this->addKeyVal('CommandeId', $commandeId));
        $orderNode->appendChild($this->addKeyVal('CommandeDate', $commandeDate . '.00'));
        $orderNode->appendChild($this->addKeyVal('Statut', 'PAYE'));        
        $orderNode->appendChild($this->addKeyVal('ModeReglement', $codeName));
        $orderNode->appendChild($this->addKeyVal('DateReglement', $dateReglement . '.00'));
        $orderNode->appendChild($this->addKeyVal('Export', 0));
        $orderNode->appendChild($this->addKeyVal('CodeSite', 1));
        $orderNode->appendChild($this->addKeyVal('Commentaire', ''));
        
        // Client
        $customer = $order->getCustomer();
        $clientNode = $this->doc->createElement('Client');
            $clientNode->appendChild($this->addKeyVal('CodeClient', $customer->getId()));
            $clientNode->appendChild($this->addKeyVal('IdGinkoiaClient', ''));
            $clientNode->appendChild($this->addKeyVal('Email', $customer->getEmail()));
            
            // Billing
            $billAddr = $order->getBillingAddress();
            $billCountry = $billAddr->getCountryCode();
            $billNode = $this->doc->createElement('AddressFact');
            $billNode->appendChild($this->addKeyVal('Civ', ''));
            $billNode->appendChild($this->addKeyVal('Nom', $billAddr->getLastname()));
            $billNode->appendChild($this->addKeyVal('Prenom', $billAddr->getFirstname()));
            $billNode->appendChild($this->addKeyVal('Ste', $billAddr->getCompany()));
            $billStreet = explode('\n', $billAddr->getStreet());
            $billNode->appendChild($this->addKeyVal('Adr1', $billStreet[0]));
            $ligneAdresse2 = isset($billStreet[1]) ? $billStreet[1] : '';
            $billNode->appendChild($this->addKeyVal('Adr2', $ligneAdresse2));
            $ligneAdresse3 = isset($billStreet[2]) ? $billStreet[2] : '';
            $billNode->appendChild($this->addKeyVal('Adr3', $ligneAdresse3));
            $billNode->appendChild($this->addKeyVal('CP', $billAddr->getPostcode()));
            $billNode->appendChild($this->addKeyVal('Ville', $billAddr->getCity()));
            $billNode->appendChild($this->addKeyVal('Pays', $billCountry));
            $billNode->appendChild($this->addKeyVal('PaysISO', $billCountry));
            $billNode->appendChild($this->addKeyVal('Tel', $billAddr->getPhoneNumber()));
            $billNode->appendChild($this->addKeyVal('Gsm', ''));
            $billNode->appendChild($this->addKeyVal('Fax', ''));
            $billNode->appendChild($this->addKeyVal('Comm', ''));
            $clientNode->appendChild($billNode);
            

            // Shipping
            //$shipAddr = Mage::getModel('sales/order_address')->load($order->getShippingAddressId());
            $shipAddr = $billAddr;
            $shipCountry = $shipAddr->getCountryCode();
            $shipNode = $this->doc->createElement('AddressLivr');
            $shipNode->appendChild($this->addKeyVal('Civ', ''));
            $shipNode->appendChild($this->addKeyVal('Nom', $shipAddr->getLastname()));
            $shipNode->appendChild($this->addKeyVal('Prenom', $shipAddr->getFirstname()));
            $shipNode->appendChild($this->addKeyVal('Ste', $shipAddr->getCompany()));
            $shipStreet = explode('\n', $shipAddr->getStreet());
            $shipNode->appendChild($this->addKeyVal('Adr1', $shipStreet[0]));
            $ligneAdresse2 = isset($shipStreet[1]) ? $shipStreet[1] : '';
            $shipNode->appendChild($this->addKeyVal('Adr2', $ligneAdresse2));
            $ligneAdresse3 = isset($shipStreet[2]) ? $shipStreet[2] : '';
            $shipNode->appendChild($this->addKeyVal('Adr3', $ligneAdresse3));
            $shipNode->appendChild($this->addKeyVal('CP', $shipAddr->getPostcode()));
            $shipNode->appendChild($this->addKeyVal('Ville', $shipAddr->getCity()));
            $shipNode->appendChild($this->addKeyVal('Pays', $shipCountry));
            $shipNode->appendChild($this->addKeyVal('PaysISO', $shipCountry));
            $shipNode->appendChild($this->addKeyVal('Tel', $shipAddr->getPhoneNumber()));
            $shipNode->appendChild($this->addKeyVal('Gsm', ''));
            $shipNode->appendChild($this->addKeyVal('Fax', ''));
            $shipNode->appendChild($this->addKeyVal('Comm', ''));
            $clientNode->appendChild($shipNode);
            
        $orderNode->appendChild($clientNode);

        
        // Colis
        if($order->hasShipments())
        {
            $store = '';
            $shipment = $order->getShipments()->first();
            $shipping_method = $shipment->getMethod()->getCode();
            if($shipping_method == 'store')
            {
                $shipping_method = 'Retait en magasin';
                $further = $order->getFurther();
                if($further && isset($further['store']) && !empty($further['store']))
                {
                    $store = $this->entityManager->getRepository(Store::class)->find($further['store']);
                    $store = $store->getName();
                }
            }
            else
            {
                $shipping_method = $shipment->getMethod()->getName();
            }
            
            //$code_carrier = $this->getCarrierCode($shipping_method, true);
            $colisNode = $this->doc->createElement('Colis');
                //$colisNode->appendChild($this->addKeyVal('Numero', ''));
                //$colisNode->appendChild($this->addKeyVal('CodeTransporteur', ''));
                $colisNode->appendChild($this->addKeyVal('Transporteur', $shipping_method));
                //$colisNode->appendChild($this->addKeyVal('CodeProduit', ''));
                $colisNode->appendChild($this->addKeyVal('MagasinRetrait', $store));
                //$colisNode->appendChild($this->addKeyVal('CodeRelais', ''));
            $orderNode->appendChild($colisNode);
        }
        

        // Lignes produits
        $lignesNode = $this->doc->createElement('Lignes');
        $orderedItems = $order->getItems();
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
                        if($ligneNode = $this->getItemNode($unitPrice, $item->getQuantity(), $ppVariant))
                        {
                            $lignesNode->appendChild($ligneNode);
                        }
                    }
                    continue;//on ne prend pas en compte les infos du pack lui-même
                }
            }
            
            if($ligneNode = $this->getItemNode($item->getUnitPrice(), $item->getQuantity(), $variant))
            {
                $lignesNode->appendChild($ligneNode);
            }
        }


        // code Promo
        $discount = $order->getOrderPromotionTotal();
        if($discount && ($discount != 0) && ($coef > 0))
        {
            $adjustements = new ArrayCollection(
                array_merge(
                    (array)$order->getAdjustmentsRecursively(AdjustmentInterface::ORDER_PROMOTION_ADJUSTMENT)->toArray(),
                    (array)$order->getAdjustmentsRecursively(AdjustmentInterface::ORDER_ITEM_PROMOTION_ADJUSTMENT)->toArray(),
                    (array)$order->getAdjustmentsRecursively(AdjustmentInterface::ORDER_UNIT_PROMOTION_ADJUSTMENT)->toArray()
                )
            );

            foreach($adjustements as $adjustement)
            {
                $ligneNode = $this->doc->createElement('Ligne');
                    $designation = '';
                    if($adjustement->getOriginCode())
                    {
                        $ligneNode->appendChild($this->addKeyVal('TypeLigne', 'CodePromo'));
                        $designation = $adjustement->getLabel() . ' : ' . $adjustement->getOriginCode();
                    }
                    else
                    {
                        // = utilisation point fidélité
                        //$ligneNode->appendChild($this->addKeyVal('TypeLigne', 'Fidelite'));
                        //$designation = $order->getCustomerId();
                        $ligneNode->appendChild($this->addKeyVal('TypeLigne', 'CodePromo'));
                        $designation = 'PointsFidélité';
                    }
                    
                    $discount = $adjustement->getAmount() / 100;//Sylius enregistre les prix en centimes
                    
                    $taxAmount = .2;
                    $valPUTTC = abs($discount) * $coef * -1;
                    $valPUHT = $valPUTTC / (1 + $taxAmount);
                    $valPUBrutTTC = $valPUTTC;
                    $valPUBrutHT = $valPUHT;
                    $valPXTTC = $valPUTTC;
                    $valPXHT = $valPUHT;
                    $valTxTva = $taxAmount * 100;
                    
                    $ligneNode->appendChild($this->addKeyVal('Code', 1));
                    $ligneNode->appendChild($this->addKeyVal('CodeEAN', ''));
                    $ligneNode->appendChild($this->addKeyVal('Designation', $designation));
                    $ligneNode->appendChild($this->addKeyVal('PUBrutHT', number_format($valPUBrutHT, 2, '.', '')));
                    $ligneNode->appendChild($this->addKeyVal('PUBrutTTC', number_format($valPUBrutTTC, 2, '.', '')));
                    $ligneNode->appendChild($this->addKeyVal('PUHT', number_format($valPUHT, 2, '.', '')));
                    $ligneNode->appendChild($this->addKeyVal('PUTTC', number_format($valPUTTC, 2, '.', '')));
                    $ligneNode->appendChild($this->addKeyVal('TxTva', number_format($valTxTva, 2, '.', '')));
                    $ligneNode->appendChild($this->addKeyVal('Qte', $coef));
                    $ligneNode->appendChild($this->addKeyVal('PXHT', number_format($valPXHT, 2, '.', '')));
                    $ligneNode->appendChild($this->addKeyVal('PXTTC', number_format($valPXTTC, 2, '.', '')));
                    $ligneNode->appendChild($this->addKeyVal('Fidelite', 0));
                $lignesNode->appendChild($ligneNode);
                
                $valTxTva = (string)$valTxTva;
                
                // totalHT et totalTTC par taux de TVA
                if(!isset($this->totaux[ $valTxTva ]))
                {
                    $this->totaux[ $valTxTva ] = [
                        'ht' => 0,
                        'ttc' => 0
                    ];
                }
                $this->totaux[ $valTxTva ]['ht'] += $valPXHT;
                $this->totaux[ $valTxTva ]['ttc'] += $valPXTTC;
            }
        }

        
        /*if(($coef < 0) && isSet($creditmemo) && ($adjustement = abs($creditmemo->getAdjustment())) && ($adjustement != 0))
        {
            $ligneNode = $this->doc->createElement('Ligne');
                $ligneNode->appendChild($this->addKeyVal('TypeLigne', 'CodePromo'));
                $designation = $creditmemo->getDiscountDescription();
                
                if(empty($designation) && ($discount = $order->getDiscountAmount()) && ($discount != 0))
                {
                    if($order->getCouponCode())
                    {
                        $designation = $order->getCouponCode();
                    }
                    else
                    {
                        $designation = 'PointsFidélité';
                    }
                }
                
                $valTxTva = 20;
                $valPUHT = abs($adjustement) * $coef;
                $valPUTTC = abs($adjustement * (1 + ($valTxTva / 100))) * $coef;
                $valPUBrutHT = $valPUHT;
                $valPUBrutTTC = $valPUTTC;
                
                $valPXHT = $valPUHT * $coef;
                $valPXTTC = $valPUTTC * $coef;
                
                $ligneNode->appendChild($this->addKeyVal('Code', 1));
                $ligneNode->appendChild($this->addKeyVal('CodeEAN', ''));
                $ligneNode->appendChild($this->addKeyVal('Designation', $designation));
                $ligneNode->appendChild($this->addKeyVal('PUBrutHT', number_format($valPUBrutHT, 2, '.', '')));
                $ligneNode->appendChild($this->addKeyVal('PUBrutTTC', number_format($valPUBrutTTC, 2, '.', '')));
                $ligneNode->appendChild($this->addKeyVal('PUHT', number_format($valPUHT, 2, '.', '')));
                $ligneNode->appendChild($this->addKeyVal('PUTTC', number_format($valPUTTC, 2, '.', '')));
                $ligneNode->appendChild($this->addKeyVal('TxTva', number_format($valTxTva, 2, '.', '')));
                $ligneNode->appendChild($this->addKeyVal('Qte', $coef));
                $ligneNode->appendChild($this->addKeyVal('PXHT', number_format($valPXHT, 2, '.', '')));
                $ligneNode->appendChild($this->addKeyVal('PXTTC', number_format($valPXTTC, 2, '.', '')));
                $ligneNode->appendChild($this->addKeyVal('Fidelite', 0));
            $lignesNode->appendChild($ligneNode);
            
            // totalHT et totalTTC par taux de TVA
            if(!isset($this->totaux[ $valTxTva ]))
            {
                $this->totaux[ $valTxTva ] = [
                    'ht' => 0,
                    'ttc' => 0
                ];
            }
            $this->totaux[ $valTxTva ]['ht'] += $valPXHT;
            $this->totaux[ $valTxTva ]['ttc'] += $valPXTTC;
        }*/
        
        $orderNode->appendChild($lignesNode);
        
        $totHT = $totTTC = 0;
        foreach($this->totaux as $mnt)
        {
            $totHT += $mnt['ht'];
            $totTTC += $mnt['ttc'];
        }
        $orderNode->appendChild($this->addKeyVal('SousTotalHT', number_format($totHT, 2, '.', '')));
        
        
        // TVAS
        $tvasNode = $this->doc->createElement('TVAS');
        $totTVA = 0;
        foreach($this->totaux as $tva => $mnt)
        {
            $mntTVA = $mnt['ttc'] - $mnt['ht'];
            $totTVA += $mntTVA;
            
            $tvaNode = $this->doc->createElement('TVA');// TVA 20%
            $tvaNode->appendChild($this->addKeyVal('TotalHT', number_format($mnt['ht'], 2, '.', '')));
            $tvaNode->appendChild($this->addKeyVal('TauxTva', number_format($tva, 2, '.', '')));
            $tvaNode->appendChild($this->addKeyVal('MtTva', number_format($mntTVA, 2, '.', '')));
            $tvasNode->appendChild($tvaNode);
        }
        $orderNode->appendChild($tvasNode);
        
        

/* TMP */
$this->doc->appendChild($orderNode);
return $this->doc;
/* TMP */
        
        // Règlements
        /*$reglementsNode = $this->doc->createElement('Reglements');
            $reglementNode = $this->doc->createElement('Reglement');
            $reglementNode->appendChild($this->addKeyVal('Mode', $codeName));
            $reglementNode->appendChild($this->addKeyVal('MontantTTC', number_format((float)$payment->getAmountOrdered(), 2, '.', '')));
            $reglementNode->appendChild($this->addKeyVal('Date', $order->getCreatedAtStoreDate()->toString('yyyy-MM-dd HH:mm:ss') . '.00'));
        $reglementsNode->appendChild($reglementNode);
        
        $orderNode->appendChild($reglementsNode);*/
        
        
        // Fin
        $shipInclTax = ($coef > 0) ? (float)$order->getAdjustmentsTotal() : (float)$creditmemo->getShippingInclTax() * $coef;
        $shipTVA = 0;//à calculer
        $shipping = $shipInclTax - $shipTVA;
        
        $totHT += $shipping;
        $totTVA += $shipTVA;
        $totTTC += $shipInclTax;
        
        $netPayer = ($coef > 0) ? (float)$payment->getAmountOrdered() : $totTTC;
        
        $orderNode->appendChild($this->addKeyVal('FraisPort', number_format($shipInclTax, 2, '.', '')));
        $orderNode->appendChild($this->addKeyVal('TotalHT', number_format($totHT, 2, '.', '')));
        $orderNode->appendChild($this->addKeyVal('MontantTVA', number_format($totTVA, 2, '.', '')));
        $orderNode->appendChild($this->addKeyVal('TotalTTC', number_format($totTTC, 2, '.', '')));
        $orderNode->appendChild($this->addKeyVal('Netpayer', number_format($netPayer, 2, '.', '')));
        
        $this->doc->appendChild($orderNode);
        
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
    private function getItemNode(int $unitPrice, int $qty, ProductVariant $variant): \DOMElement
    {
        /*if($coef < 0) 
        {
            $qty = (int)$item->getQtyRefunded();
            if($qty <= 0) continue;// on ne génère pas de lignes pour les produits non-remboursés
            $qty *= $coef;
        }*/

        $taxAmount = $variant->getTaxCategory()->getRates()->first()->getAmount();
        $valPUTTC = $unitPrice / 100;//Sylius enregistre les prix en centimes
        $valPUHT = $valPUTTC / (1 + $taxAmount);
        $valPUBrutTTC = $variant->getChannelPricingForChannel($this->channel)->getPrice() / 100;
        $valPUBrutHT = $valPUBrutTTC / (1 + $taxAmount);
        $valTxTva = $taxAmount * 100;
        
        $valPXHT = $valPUHT * $qty; // pas besoin de multiplier par $coef vu que...
        $valPXTTC = $valPUTTC * $qty; // ...$qty l'utilise déjà pour un credimemo
        
        $ligneNode = $this->doc->createElement('Ligne');
            $ligneNode->appendChild($this->addKeyVal('TypeLigne', 'Ligne'));
            $ligneNode->appendChild($this->addKeyVal('Code', $variant->getCode()));
            $ligneNode->appendChild($this->addKeyVal('CodeEAN', ''));
            $ligneNode->appendChild($this->addKeyVal('Designation', $variant->getName()));
            $ligneNode->appendChild($this->addKeyVal('PUBrutHT', number_format($valPUBrutHT, 2, '.', '')));
            $ligneNode->appendChild($this->addKeyVal('PUBrutTTC', number_format($valPUBrutTTC, 2, '.', '')));
            $ligneNode->appendChild($this->addKeyVal('PUHT', number_format($valPUHT, 2, '.', '')));
            $ligneNode->appendChild($this->addKeyVal('PUTTC', number_format($valPUTTC, 2, '.', '')));
            $ligneNode->appendChild($this->addKeyVal('TxTva', number_format($valTxTva, 2, '.', '')));
            $ligneNode->appendChild($this->addKeyVal('Qte', $qty));
            $ligneNode->appendChild($this->addKeyVal('PXHT', number_format($valPXHT, 2, '.', '')));
            $ligneNode->appendChild($this->addKeyVal('PXTTC', number_format($valPXTTC, 2, '.', '')));
            $ligneNode->appendChild($this->addKeyVal('Fidelite', 0));
        
        
        // transformation en chaine de caractères
        // pour utiliser en tant que clé de tableau
        $valTxTva = (string)$valTxTva;
        
        // totalHT et totalTTC par taux de TVA
        if(!isset($this->totaux[ $valTxTva ]))
        {
            $this->totaux[ $valTxTva ] = [
                'ht' => 0,
                'ttc' => 0
            ];
        }
        $this->totaux[ $valTxTva ]['ht'] += $valPXHT;
        $this->totaux[ $valTxTva ]['ttc'] += $valPXTTC;

        return $ligneNode;
    }
}