<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Chullanka\Parameter;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class GinkoiaCustomerWs
{
    const WS_GCF_ID = '102375';
    const POINTS_4_COUPON = 500;
    const COUPON_DISCOUNT = 10;
    
    private $entityManager;
    private $logger;

    public function __construct(EntityManagerInterface $entityManager, LoggerInterface $logger)
    {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
    }
    private function chkParameter($slug)
    {
        return $this->entityManager->getRepository(Parameter::class)->getValue($slug);
    }

    /**
     * Envoie la requête au WS
     * @param array $data
     * @param string $type
     * @return mixed
     */
    private function _callWS($data, $type = 'GET')
    {
        $wsUrl = $this->chkParameter('ginkoia-ws-url');
        $wsUser = $this->chkParameter('ginkoia-ws-user');
        $wsPwd = $this->chkParameter('ginkoia-ws-pass');
        if(empty($wsUrl) || empty($wsUser) || empty($wsPwd))
        {
            return 'Ginkoia Ws:: URL, User ou Pass manquant.';
        }

        $data['user'] = $wsUser;
        $data['pwd'] = $wsPwd;
        
        // if Loyalty sub-service...
        if(isset($data['loyalty_id']))
        {
            $wsUrl .= '/loyalty';
        }
        if(isset($data['loyalty_points']))
        {
            $wsUrl .= '/usepoints';
        }
        if(isset($data['voucher_id']))
        {
            $wsUrl .= '/loyalty/usevoucher';
        }

        // ReceiptsList
        if(isset($data['ReceiptsList']) && ($data['ReceiptsList'] == true))
        {
            unset($data['ReceiptsList']);
            $wsUrl .= '/ReceiptsList';
        }
        if(isset($data['ReceiptID']))
        {
            $wsUrl = str_replace('/customer', '', $wsUrl) . '/ReceiptDetail';
        }
        $this->logger->info('Ginkoia Ws::URL : '.$wsUrl.' | DATA : '.json_encode($data));
        
        // init cURL
        $ch = curl_init($wsUrl);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $type);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        
        $json_response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        curl_close($ch);
        
        if($http_code != 200) 
        {
            $this->logger->info('Ginkoia Ws:: Pb pour accéder au WS avec code de retour HTTP : '.$http_code);
            return $json_response;
            return 'Ginkoia Ws:: Pb pour accéder au WS avec code de retour HTTP : '.$http_code;
        }
        
        $json_response = str_replace("\\", "/", $json_response); // spécifique à leur WS sous Windows
        return json_decode($json_response, true);
    }
    
    /**
     * Récupère les infos d'un client par rapport à son email
     * @param string $email
     * @return mixed
     */
    public function getCustomerInfos($email)
    {
        if($return = $this->_callWS(['email' => $email]))
        {
            return (isset($return['Result']) && ($return['Result'] == 'OK')) ? $return['Customer'] : $return;
        }
        
        return false;
    }
    
    /**
     * 
     * @param array $user
     * @param string $email for update
     * @return array|mixed|boolean
     */
    public function setCustomerInfos($user, $email = '')
    {
        $newData = ['Customer' => $user];
        
        if(!empty($email)) $newData['email'] = $email;
        
        error_log('setCustomerInfos');
        error_log(print_r($newData,true));
        if($return = $this->_callWS($newData, 'POST'))
        {
            return (isset($return['Result']) && ($return['Result'] == 'OK')) ? $return['Customer'] : $return;
        }
        return false;
    }
    
    /**
     * Récupère les infos de fidélité d'un client
     * @param string $email
     * @return string[]|mixed|boolean
     */
    public function getCustomerLoyalties($email)
    {
        $data = [
            'loyalty_id' => self::WS_GCF_ID,
            'email' => $email
        ];
        
        if($return = $this->_callWS($data))
        {
            if(isset($return['Result']) && ($return['Result'] == 'OK'))
            {
                $loyalties = [
                    'loyalty_points' => $return['loyalty_points'],
                    'loyalty_total_points' => $return['loyalty_total_points'],
                    'vouchers' => $return['vouchers']
                ];
                return $loyalties;
                //return $return['vouchers'];
            }
            else return $return;
        }
        
        return false;
    }
    
    /**
     * Utilisation de points
     * @param int $points
     * @param string $email
     * @return boolean|array
     */
    public function usePoints($points, $email)
    {
        $data = [
            'loyalty_id' => self::WS_GCF_ID,
            'loyalty_points' => $points,
            'email' => $email
        ];
        
        if($return = $this->_callWS($data))
        {
            return (isset($return['Result']) && ($return['Result'] == 'OK')) ? true : $return;
        }
        
        return false;
    }
    
    /**
     * Utilisation de bons d'achat
     * @param string $voucher_id
     * @return boolean|array
     */
    public function useVoucher($voucher_id)
    {
        if($return = $this->_callWS(['voucher_id' => $voucher_id]))
        {
            return (isset($return['Result']) && ($return['Result'] == 'OK')) ? true : $return;
        }
        
        return false;
    }

    /**
     * Récupère les commandes passées en magasin par un client par rapport à son email
     * @param string $email
     * @return mixed
     */
    public function getCustomerShopOrders($email)
    {
        if($return = $this->_callWS(['email' => $email, 'ReceiptsList' => true]))
        {
            return (isset($return['Result']) && ($return['Result'] == 'OK')) ? $return['ReceiptsList'] : $return;
        }
        return false;
    }

    /**
     * Récupère les détails d'une commande via son ReceiptID
     * @param string $receiptID
     * @return mixed
     */
    public function getCustomerReceiptDetail($receiptID)
    {
        if($return = $this->_callWS(['ReceiptID' => $receiptID]))
        {
            return (isset($return['Result']) && ($return['Result'] == 'OK')) ? $return['ReceiptDetail'] : $return;
        }
        return false;
    }
}