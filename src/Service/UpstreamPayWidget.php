<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Payment\GatewayConfig;
use App\Entity\Product\ProductVariant;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class UpstreamPayWidget
{
    private $entityManager;
    private $session;
    private $logger;
    private $router;
    private $client_id;
    private $client_secret;
    private $upstreampay_session;
    public $upstreampay_base_url;
    public $api_key;
    public $entity_id;

    public function __construct(EntityManagerInterface $entityManager, SessionInterface $session, LoggerInterface $logger, UrlGeneratorInterface $router)
    {
        $this->entityManager = $entityManager;
        $this->session = $session;
        $this->logger = $logger;
        $this->router = $router;
        
        if(($gatewayConfig = $this->entityManager->getRepository(GatewayConfig::class)->findOneBy(['gatewayName' => 'upstream_pay'])) && ($config = $gatewayConfig->getConfig()))
        {
            $this->client_id = $config['client_id'];//'0oa2y332wdiqfYIje417';
            $this->client_secret = $config['client_secret'];//'5d67RZR3KgXg_5bJNYoXoC9IodZvrj98uxnNDj3q';
            $this->api_key = $config['api_key'];//'20d52d3a-f0de-434e-9b15-6c6d642faead';
            $this->entity_id = $config['entity_id'];//'ead278b3-8f52-4257-8e46-dea8813461a8';
            $this->upstreampay_base_url = $config['base_url'];//'https://api.preprod.upstreampay.com';
        }
    }

    public function getToken()
    {
        if(is_null($this->session->get('upstreampay_token')) || empty($this->session->get('upstreampay_token')) || ($this->session->get('upstreampay_token_expire') < time()))
        {
            $authUrl = $this->upstreampay_base_url . '/oauth/token';
            $basic_auth = base64_encode($this->client_id . ':' . $this->client_secret);

            $ch = curl_init($authUrl);
            $customHeaders = [
                'Authorization: Basic '.$basic_auth,
                'Cache-Control: no-cache',
                'x-api-key: '.$this->api_key,
            ];
            curl_setopt($ch, CURLOPT_HTTPHEADER, $customHeaders);

            curl_setopt($ch, CURLOPT_POSTFIELDS, 'grant_type=client_credentials');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            $json_response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if($http_code != 200)
            {
                return "<p style='color:red; font-weight:700'>Erreur d'authentification 1</p>";
            }
            $response = json_decode($json_response);

            $this->session->set('upstreampay_token', $response->access_token);
            $this->session->set('upstreampay_token_expire', time() + $response->expires_in);
        }
        return $this->session->get('upstreampay_token');
    }

    public function getUpStreamPaySession($order = null)
    {
        error_log("getUpStreamPaySession");
        $createSessionUrl = $this->upstreampay_base_url . '/' . $this->entity_id . '/sessions/create';
        error_log("createSessionUrl : $createSessionUrl");
        $ch = curl_init($createSessionUrl);
        $customHeaders = [
            'Content-Type: application/json',
            'x-api-key: '.$this->api_key,
            'Authorization: Bearer '.$this->getToken()
        ];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $customHeaders);
        
        $data = $this->getFormattedData($order);
        $this->logger->info('getUpStreamPaySession | DATA : '.$data);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        //curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        
        $json_response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $this->logger->info('http_code : '.$http_code);
        curl_close($ch);
        
        if(self::isJSON($json_response))
        {
            $response = json_decode($json_response);
            //$this->logger->info('response : '.print_r($response,true));
            if(isset($response->id))
            {
                $this->upstreampay_session = $json_response;
                $this->session->set('upstreampay_session_id', $response->id);
                return $json_response;
            }
        }
        $this->logger->info('json_response : '.$json_response);

        return '{}';
    }
    
    public function getSessionId()
    {
        if((is_null($this->session->get('upstreampay_session_id')) || empty($this->session->get('upstreampay_session_id'))) && self::isJSON($this->upstreampay_session))
        {
            $upstreampay_session = json_decode($this->upstreampay_session);
            $this->session->set('upstreampay_session_id', $upstreampay_session->id);
        }
        return $this->session->get('upstreampay_session_id');
    }

    public function getSessionInfos($sessionId = null)
    {
        if(is_null($sessionId)) $sessionId = $this->getSessionId();

        if($sessionId)
        {
            $askSessionUrl = $this->upstreampay_base_url . '/' . $this->entity_id . '/sessions/' . $sessionId;
            $ch = curl_init($askSessionUrl);
            $customHeaders = [
                'x-api-key: '.$this->api_key,
                'Authorization: Bearer '.$this->getToken(),
                //'Content-Type: application/json'
            ];
            curl_setopt($ch, CURLOPT_HTTPHEADER, $customHeaders);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            //curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
            
            $json_response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            error_log("code : $http_code");
            error_log($json_response);
    
            return json_decode($json_response);
        }
        else error_log("pas de session ID");
        return false;
    }

    public function cancelOrRefund($infos, $action)
    {
        $transactionId = $infos->transaction_id;
        $amount = $infos->plugin_result->amount;

        $cancelUrl = $this->upstreampay_base_url . '/' . $this->entity_id . '/transactions/' . $transactionId . '/' . $action;
        $ch = curl_init($cancelUrl);
        $customHeaders = [
            'Content-Type: application/json',
            'x-api-key: '.$this->api_key,
            'Authorization: Bearer '.$this->getToken()
        ];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $customHeaders);
        
        $data = [
            'amount' => $amount,
            'order' => [
                'amount' => $amount,
                'currency_code' => 'EUR'
            ]
        ];
        $data = json_encode($data);
        error_log($data);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        //curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        
        $json_response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        error_log("http_code : $http_code");
        curl_close($ch);
        
        $response = json_decode($json_response);
        if(isset($response->id))
        {
            return $json_response;
        }
        else error_log($json_response);

        return;
    }

    public function getFormattedData($order)
    {
        $data = [];
        $urlHook = $urlSuccess = $urlFailure = '';
        $customer = $order->getCustomer();
        foreach($order->getPayments() as $payment)
        {
            if($payment->getState() == 'cart')
            {
                $payMethod = $payment->getMethod();
                if($payMethod->getCode() == 'UPSTREAM_PAY')
                {
                    $urlHook = $this->router->generate('chk_upstream_payment_return', ['hook' => 1], UrlGeneratorInterface::ABSOLUTE_URL);
                    $urlSuccess = $this->router->generate('chk_upstream_payment_return', ['success' => 1], UrlGeneratorInterface::ABSOLUTE_URL);
                    $urlFailure = $this->router->generate('chk_upstream_payment_return', ['failure' => 1], UrlGeneratorInterface::ABSOLUTE_URL);

                    $total_amount = $payment->getAmount() / 100;
                    $net_amount = 0;

                    $item_lines = [];
                    $total_tax_lines = [];

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
                                    
                                    $valPUTTC = $unitPrice / 100;
                                    $tax = $ppVariant->getTaxCategory()->getRates()->first();
                                    $taxAmount = $tax->getAmount();
                                    $valPUHT = $valPUTTC / (1 + $taxAmount);
                                    $net_amount += $valPUHT;
                                    
                                    $tax_lines = [
                                        'type_code' => 'vat',
                                        'subtype_code' => $tax->getCode(),
                                        'rate' => ($taxAmount * 100),
                                        'amount' => round($valPUTTC - $valPUHT, 2)
                                    ];
                                    $item_line = [
                                        'type_code' => 'product',
                                        'sku_reference' => $ppVariant->getCode(),
                                        'name' => $ppVariant->getName(),
                                        'price' => $valPUTTC,
                                        'quantity' => $item->getQuantity(),
                                        'amount' => ($item->getQuantity() * $valPUTTC),
                                        'tax_lines' => [ $tax_lines ]
                                    ];
                                    $item_lines[] = $item_line;
                                    $total_tax_lines[ ($taxAmount * 100) ][] = $tax_lines;
                                }
                                continue;//on ne prend pas en compte les infos du pack lui-même
                            }
                        }

                        $valPUTTC = $item->getUnitPrice() / 100;
                        $tax = $variant->getTaxCategory()->getRates()->first();
                        $taxAmount = $tax->getAmount();
                        $valPUHT = $valPUTTC / (1 + $taxAmount);
                        $net_amount += $valPUHT;

                        $tax_lines = [
                            'type_code' => 'vat',
                            'subtype_code' => $tax->getCode(),
                            'rate' => ($taxAmount * 100),
                            'amount' => round($valPUTTC - $valPUHT, 2)
                        ];
                        $item_line = [
                            'type_code' => 'product',
                            'sku_reference' => $variant->getCode(),
                            'name' => $variant->getName(),
                            'price' => $valPUTTC,
                            'quantity' => $item->getQuantity(),
                            'amount' => ($item->getQuantity() * $valPUTTC),
                            'tax_lines' => [ $tax_lines ]
                        ];
                        $item_lines[] = $item_line;
                        $total_tax_lines[ ($taxAmount * 100) ][] = $tax_lines;
                    }

                    // shipment_lines
                    $delivery_point_name = '';
                    if($order->hasShipments())
                    {
                        $shipment = $order->getShipments()->first();
                        $shipmethod = $shipment->getMethod();
                        $shipInclTax = (float)$order->getAdjustmentsTotal() / 100;
                        $taxAmount = .2;
                        $shipping = $shipInclTax / (1 + $taxAmount);
                        $shipTVA = $shipInclTax - $shipping;
                        $tax_lines = [
                            'type_code' => 'vat',
                            'subtype_code' => 'tva20',
                            'rate' => ($taxAmount * 100),
                            'amount' => round($shipTVA, 2)
                        ];

                        $delivery_point_name = explode('-', $shipmethod->getCode());
                        $delivery_point_name = $delivery_point_name[0];

                        $item_line = [
                            'type_code' => 'shipping_fees',
                            'sku_reference' => $shipmethod->getCode(),
                            'name' => $shipmethod->getName(),
                            'price' => $shipInclTax,
                            'quantity' => 1,
                            'amount' => $shipInclTax,
                            'tax_lines' => [ $tax_lines ]
                        ];
                        $item_lines[] = $item_line;
                        $total_tax_lines[ ($taxAmount * 100) ][] = $tax_lines;
                    }

                    $tax_amount = round($total_amount - $net_amount, 2);
                    $shipAddr = $order->getShippingAddress();

                    $shipStreet = $shipAddr->getStreet();
                    if(strlen($shipStreet) > 32) $shipStreet = substr($shipStreet, 0, 32);

                    $shipment_line = [
                        'amount' => $total_amount,
                        'net_amount' => round($net_amount, 2),
                        'tax_amount' => $tax_amount,
                        'shipping_address' => [
                            'first_name' => $shipAddr->getFirstname(),
                            'last_name' => $shipAddr->getLastname(),
                            'address_lines' => [ $shipStreet ],
                            'city' => $shipAddr->getCity(),
                            'postal_code' => $shipAddr->getPostcode(),
                            'country_code' => $shipAddr->getCountryCode(),
                            'province_code' => $shipAddr->getProvinceCode(),
                            'email' => $customer->getEmail(),
                            'phone' => $shipAddr->getPhoneNumber()
                        ],
                        'seller_reference' => 'Chullanka',
                        'item_lines' => $item_lines
                    ];

                    //availables gender values
                    $genders = ['u' => 'unknown', 'm' => 'male', 'f' => 'female'];
                    $gender = 'unknown';
                    if(!empty($customer->getGender()))
                    {
                        $gender = $genders[ $customer->getGender() ];
                    }

                    $address = $order->getBillingAddress();

                    $billStreet = $address->getStreet();
                    //order.customer.billing_address.address_lines[0] length cannot be greater than 32 or less than 0
                    if(strlen($billStreet) > 32) $billStreet = substr($billStreet, 0, 32);

                    $customer_lines = [
                        'reference' => 'chk_customer_' . $customer->getId(), //utilisé pour réafficher des paiements la deuxième fois
                        'type_code' => 'customer',
                        'company_name' => $address->getCompany(),
                        'gender_code' => $gender,
                        'first_name' => $customer->getFirstname(),
                        'last_name' => $customer->getLastname(),
                        'ip' => $order->getCustomerIp(),
                        'locale_code' => 'fr_FR',
                        'billing_address' => [
                            'delivery_point_name' => $delivery_point_name,
                            'gender_code' => $gender,
                            'first_name' => $address->getFirstname(),
                            'last_name' => $address->getLastname(),
                            'address_lines' => [ $billStreet ],
                            'city' => $address->getCity(),
                            'postal_code' => $address->getPostcode(),
                            'country_code' => $address->getCountryCode(),
                            'province_code' => $address->getProvinceCode(),
                            'email' => $customer->getEmail(),
                            'phone' => $address->getPhoneNumber()
                        ]
                    ];
                    if($customer->getBirthday())
                    {
                        $customer_lines['birthdate'] = $customer->getBirthday()->format('Y-m-d');
                    }

                    if($customer->hasUser())
                    {
                        $customer_lines['account'] = [
                            'authentication_method' => 'MERCHANT_CREDENTIALS',
                            'authentication_date_time' => $customer->getUser()->getLastLogin()->format('Y-m-d\TH:i:s')
                        ];
                    }

                    //tax_lines
                    $final_tax_lines = [];
                    foreach($total_tax_lines as $taux => $array)
                    {
                        foreach($array as $line)
                        {
                            if(!isset($final_tax_lines[ $taux ])) $final_tax_lines[ $taux ] = $line;
                            else $final_tax_lines[ $taux ]['amount'] += $line['amount'];
                        }
                    }
                    $final_tax_lines = array_values($final_tax_lines);

                    $shipments = [
                        $shipment_line
                    ];
                    $order = [
                        'reference' => 'chk_' . $order->getId(),
                        'success' =>  $urlSuccess,
                        'failure' => $urlFailure,
                        'amount' => $total_amount,
                        'net_amount' => round($net_amount, 2),
                        'tax_amount' => $tax_amount,
                        'currency_code' => $order->getCurrencyCode(),
                        'tax_lines' => $final_tax_lines,
                        'customer' => $customer_lines,
                        'shipments' => $shipments,
                    ];

                    $data = [
                        'hook' => $urlHook,
                        'amount' => $total_amount,
                        'order' => $order
                    ];
                }
                break;//sortie de boucle foreach
            }
        }
        //echo "<pre>";print_r($data);die;
        $json = str_replace('\/', '/', json_encode($data));
        return $json;


        /*
        Pour FLOA (dev du plugin en cours) voici les champs qui seront obligatoires/nécessaires pour transmission à FLOA dans le cadre du paiement 3X et limiter le re-saisie de l’acheteur sur le formulaire suivant
Customer
    reference
    gender_code
    first_name
    last_name
    birthdate
Adress
    mobile_phone
    email
    address_lines
    city
    postal_code
    country_code
Order
    reference
    amount
    currency_code
Items_lines
    type_code
    price
    quantity
    amount
Tax_lines
    Tout
Shipments
    delivery_type_code
    delivery_method_reference
        */

        $datavalid = 
            '{
            "hook": "' . $urlHook . '",
            "amount": 175,
            "order": {
                "reference": "FR123456789",
                "success": "' . $urlSuccess . '",
                "failure": "' . $urlFailure . '",
                "amount": 175.00,
                "net_amount": 150.86,
                "tax_amount": 24.14,
                "currency_code": "EUR",
                "tax_lines": [
                    {
                    "type_code": "vat",
                    "subtype_code": "standard",
                    "rate": 16,
                    "amount": 24.14
                    },
                    {
                    "type_code": "vat",
                    "subtype_code": "standard",
                    "rate": 0,
                    "amount": 0
                    }
                ],
                "customer": {
                    "reference": "2090000000000",
                    "type_code": "customer",
                    "company_name": "Schuppe, Hayes and Veum",
                    "gender_code": "male",
                    "first_name": "Miles",
                    "middle_name": "Eva",
                    "last_name": "MORALES",
                    "birthdate": "1995-08-25",
                    "ip": "127.0.0.1",
                    "locale_code": "es-MX",
                    "billing_address": {
                    "delivery_point_name": "home",
                    "gender_code": "male",
                    "first_name": "Miles",
                    "middle_name": "Eva",
                    "last_name": "Pasteur",
                    "address_lines": [
                        "Av. Revolución 1676",
                        "Zona Centro"
                    ],
                    "city": "Tijuana",
                    "postal_code": "22000",
                    "country_code": "MX",
                    "province_code": "MX-BCN",
                    "email": "miles.morales@marvel.com",
                    "phone": "+52 664 5951 538",
                    "mobile_phone": "+52 664 5951 538",
                    "work_phone": "+52 664 5951 538"
                    },
                    "account": {
                        "purchase_count_last_day": 0,
                        "purchase_count_last_six_months": 42,
                        "purchase_count_last_year": 100,
                        "different_card_count_last_day": 1,
                        "authentication_method": "MERCHANT_CREDENTIALS",
                        "authentication_date_time": "2022-01-05T16:42:20.733Z",
                        "prior_payment_authentication": "ACS_CHALLENGE",
                        "prior_payment_authentication_date_time": "2021-02-28T02:32:22.065Z",
                        "prior_payment_authentication_preference": "xxxxx-xxxxxx-xxxxxx-xxxxx",
                        "age_indicator": "BETWEEN_30_60_DAYS",
                        "update_date_time": "2021-04-24T23:56:13.692Z",
                        "change_indicator": "MORE_60_DAYS",
                        "creation_date_time": "2021-08-09T23:32:48.939Z",
                        "password_update_date_time": "2022-01-24T08:28:57.432Z",
                        "password_change_indicator": "MORE_60_DAYS"
                    },
                    "additional_attributes": {
                    "national_identifier": "MX-1234567890"
                    }
                },
                "shipments": [
                    {
                    "amount": 175,
                    "net_amount": 150.86,
                    "tax_amount": 24.14,
                    "delivery_type_code": "external_pickup",
                    "delivery_quickness_code": "regular",
                    "estimated_delivery_date_time": "2020-08-25T10:42:59+02:00",
                    "shipping_address": {
                        "delivery_point_name": "home",
                        "gender_code": "male",
                        "first_name": "Miles",
                        "middle_name": "Eva",
                        "last_name": "Pasteur",
                        "address_lines": [
                        "Av. Revolución 1676",
                        "Zona Centro"
                        ],
                        "city": "Tijuana",
                        "postal_code": "22000",
                        "country_code": "MX",
                        "province_code": "MX-BCN",
                        "email": "miles.morales@marvel.com",
                        "phone": "+52 664 5951 538",
                        "mobile_phone": "+52 664 5951 538",
                        "work_phone": "+52 664 5951 538"
                    },
                    "seller_reference": "UpStream Pay shop",
                    "item_lines": [
                        {
                        "type_code": "product",
                        "sku_reference": "2600218",
                        "name": "Camiseta100 niño GYM",
                        "price": 85,
                        "quantity": 1,
                        "amount": 85,
                        "tax_lines": [
                            {
                            "type_code": "vat",
                            "subtype_code": "standard",
                            "rate": 16,
                            "amount": 11.73
                            }
                        ]
                        },
                        {
                        "type_code": "shipping_fees",
                        "sku_reference": "2102120",
                        "name": "Transporte",
                        "price": 0,
                        "quantity": 1,
                        "amount": 0,
                        "tax_lines": [
                            {
                            "type_code": "vat",
                            "subtype_code": "standard",
                            "rate": 0,
                            "amount": 0
                            }
                        ]
                        }
                    ],
                    "tax_lines": [
                        {
                        "type_code": "vat",
                        "subtype_code": "standard",
                        "rate": 16,
                        "amount": 24.14
                        },
                        {
                        "type_code": "vat",
                        "subtype_code": "standard",
                        "rate": 0,
                        "amount": 0
                        }
                    ]
                    },
                    {
                    "amount": 175,
                    "net_amount": 150.86,
                    "tax_amount": 24.14,
                    "delivery_type_code": "user_delivery",
                    "delivery_quickness_code": "express",
                    "delivery_method_reference": "cash_on_delivery",
                    "estimated_delivery_date_time": "2020-08-25T10:42:59+02:00",
                    "shipping_address": {
                        "delivery_point_name": "home",
                        "gender_code": "male",
                        "first_name": "Miles",
                        "middle_name": "Eva",
                        "last_name": "Pasteur",
                        "address_lines": [
                        "Av. Revolución 1676",
                        "Zona Centro"
                        ],
                        "city": "Tijuana",
                        "postal_code": "22000",
                        "country_code": "MX",
                        "province_code": "MX-BCN",
                        "email": "miles.morales@marvel.com",
                        "phone": "+52 664 5951 538",
                        "mobile_phone": "+52 664 5951 538",
                        "work_phone": "+52 664 5951 538"
                    },
                    "seller_reference": "752b764c-4333-4d98-9d98-6eddf80c84bf",
                    "item_lines": [
                        {
                        "type_code": "product",
                        "sku_reference": "2600218",
                        "name": "Camiseta100 niño GYM",
                        "price": 85,
                        "quantity": 1,
                        "amount": 85,
                        "tax_lines": [
                            {
                            "type_code": "vat",
                            "subtype_code": "standard",
                            "rate": 16,
                            "amount": 11.73
                            }
                        ]
                        },
                        {
                        "type_code": "shipping_fees",
                        "sku_reference": "2102120",
                        "name": "Transporte",
                        "price": 0,
                        "quantity": 1,
                        "amount": 0,
                        "tax_lines": [
                            {
                            "type_code": "vat",
                            "subtype_code": "standard",
                            "rate": 0,
                            "amount": 0
                            }
                        ]
                        }
                    ],
                    "tax_lines": [
                        {
                        "type_code": "vat",
                        "subtype_code": "standard",
                        "rate": 16,
                        "amount": 24.14
                        },
                        {
                        "type_code": "vat",
                        "subtype_code": "standard",
                        "rate": 0,
                        "amount": 0
                        }
                    ]
                    }
                ]
                }
            }'
        ;
        return $datavalid;
    }

    /**
     * Test if string is a valid JSON
     */
    public static function isJSON($string)
    {
        return is_string($string) && is_array(json_decode($string, true)) && (json_last_error() === JSON_ERROR_NONE) ? true : false;
     }
    
}