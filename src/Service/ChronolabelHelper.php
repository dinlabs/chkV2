<?php

declare(strict_types=1);

namespace App\Service;

class ChronolabelHelper
{
    public function getTransportLabel()
	{
        /*if(!isset($data['address']))
        {
            //die('<div style="color:red; font-weight:700;">L\'adresse est obligatoire</div>');
            //$data['address'] = "17 Boulevard de la Prairie au Duc";
            $data['address'] = '';
        }
        $address = $data['address'];
		$address = mb_convert_encoding(urldecode($address),'UTF-8');
		$address = self::stripAccents($address);
		
        if(!isset($data['zip']) || empty($data['zip']))
        {
            die('<div style="color:red; font-weight:700;">Le code postal est obligatoire</div>');
            //$data['zip'] = "44200";
        }
        $zipcode = $data['zip'];
		$zipcode = trim(urldecode($zipcode));
		$zipcode = mb_convert_encoding($zipcode,'UTF-8');
		
        if(!isset($data['city']) || empty($data['city']))
        {
            die('<div style="color:red; font-weight:700;">La ville est obligatoire</div>');
            //$data['city'] = "Nantes";
        }
        $city = $data['city'];
		$city = mb_convert_encoding(urldecode($city),'UTF-8');
		$city = self::stripAccents($city);*/
        
        // Message d'erreur si PHP_SOAP manquant
        if(!extension_loaded('soap'))
        echo '<div style="color:red; font-weight:700;">ATTENTION! L\'extension PHP SOAP n\'est pas activée sur ce serveur. Vous devez l\'activer pour utiliser le module DPD Relais.</div>';
        // Appel WS
        try
        {
            $serviceurl = 'https://ws.chronopost.fr/shipping-cxf/ShippingServiceWS?wsdl';
            $client = new \SoapClient($serviceurl, [
                'connection_timeout' => 10,
                'trace' => 0
            ]);
    
            $esdValue = [
                'retrievalDateTime' => '',
                'closingDateTime' => '',
                'shipperBuildingFloor' => '',
                'shipperCarriesCode>' => '',
                'shipperServiceDirection' => '',
                'specificInstructions' => '',
                'ltAImprimerParChronopost' => 'N',
                'nombreDePassageMaximum' => 1,
                'refEsdClient' => '',
                'codeDepotColReq' => '',
                'numColReq' => '',
                'height' => 10,
                'length' => 10,
                'width' => 10
            ];

            $headerValue = [
                'accountNumber' => 19869502,
                'idEmit' => '',
                'identWebPro' => '',
                'subAccount' => 0
            ];

            $shipperValue = [
                'shipperType' => 2,
                'shipperName' => 'SENDER NAME 1',
                'shipperName2' => 'SENDER NAME 2',
                'shipperCivility' => 'M',
                'shipperContactName' => '',
                'shipperAdress1' => 'SENDER ADDRESS 1',
                'shipperAdress2' => 'SENDER ADDRESS 2',
                'shipperZipCode' => '94000',
                'shipperCity' => 'SENDER CITY',
                'shipperCountry' => 'FR',
                'shipperCountryName' => 'FRANCE',
                'shipperEmail' => 'sender@provider.com',
                'shipperPhone' => '0102030405',
                'shipperMobilePhone' => '',
                'shipperPreAlert' => 0
            ];

            $customerValue = [
                'customerName' => 'Chullanka.com',
                'customerName2' => '',
                'customerCivility' => '',
                'customerContactName' => '',
                'customerAdress1' => '2222 route de Grasse',
                'customerAdress2' => '',
                'customerZipCode' => '06600',
                'customerCity' => 'ANTIBES',
                'customerCountry' => 'FR',
                'customerCountryName' => 'FRANCE',
                'customerEmail' => 'contact.vente@chullanka.com',
                'customerPhone' => '0492917900',
                'customerMobilePhone' => '',
                'customerPreAlert' => 0,
                'printAsSender' => 'N'
            ];

            $recipientValue = [
                'recipientName' => 'Chullanka Retour Web',
                'recipientName2' => '',
                'recipientCivility' => '',
                'recipientContactName' => '',
                'recipientAdress1' => '1 chemin de la coume',
                'recipientAdress2' => '',
                'recipientZipCode' => '09300',
                'recipientCity' => 'LAVELANET',
                'recipientCountry' => 'FR',
                'recipientCountryName' => 'FRANCE',
                'recipientEmail' => '',
                'recipientPhone' => '0412042949',
                'recipientMobilePhone' => '',
                'recipientPreAlert' => 0
            ];

            $refValue = [
                'customerSkybillNumber' => '',
                'PCardTransactionNumber' => '',
                'recipientRef' => 'CONSIGNEE CODE',
                'shipperRef' => 'SHIPPER REFRENCE',
                'idRelais' => '',
            ];

            $skybillValue = [
                'bulkNumber' => 1,
                'skybillRank' => 1,
                'masterSkybillNumber' => '',
                'codCurrency' => 'EUR',
                'codValue' => '',
                'customsCurrency' => 'EUR',
                'customsValue' => 0,
                'insuredCurrency' => 'EUR',
                'insuredValue' => '0.0',
                'portCurrency' => '',
                'portValue' => '0.0',
                'evtCode' => 'DC',
                'objectType' => 'MAR',
                'productCode' => '01',
                'service' => 0,
                'shipDate' => '2020-03-03T12:29:36+02:00',
                'shipHour' => 12,
                'weight' => '2.0',
                'weightUnit' => 'KGM',
                'height' => 0,
                'length' => 0,
                'width' => 0,
                'content1' => '',
                'content2' => '',
                'content3' => '',
                'content4' => '',
                'content5' => '',
                'latitude' => '',
                'longitude' => '',
                'qualite' => '',
                'source' => '',
                'as' => '',
                'toTheOrderOf' => '',
                'skybillNumber' => '',
                'carrier' => '',
                'skybillBackNumber' => ''
            ];

            $skybillParamsValue = [
                'duplicata' => 'N',
                'mode' => 'PPR',
                'withReservation' => 0
            ];

            $params = [
                'esdValue'              => $esdValue,
                'headerValue'           => $headerValue,
                'shipperValue'          => $shipperValue,
                'customerValue'         => $customerValue,
                'recipientValue'        => $recipientValue,
                'refValue'              => $refValue,
                'skybillValue'          => $skybillValue,
                'skybillParamsValue'    => $skybillParamsValue,
                'password'              => 255562,
                'modeRetour'            => 2,
                'numberOfParcel'        => 1,
                //'version'               => '2.0',
                'multiParcel'           => 'N',
                //'scheduledValue'        => '',
                //'recipientLocalValue'   => '',
                //'customsValue'          => '',
            ];
            $webservbt = $client->shippingMultiParcelV3($params);

            if($webservbt->return->errorCode == 0)
            {
                return $webservbt->return->resultMultiParcelValue;
            }
        }
        catch (\Exception $e)
        {
            echo '<div stlye="color:red; font-weight:bold;">Une erreur s\'est produite lors de la création de votre étiquette de retour. Merci de réessayer.</div>';
            exit;
        }
        return false;
	}

    public static function stripAccents($str)
	{
		$str = preg_replace('/[\x{00C0}\x{00C1}\x{00C2}\x{00C3}\x{00C4}\x{00C5}]/u','A', $str);
		$str = preg_replace('/[\x{0105}\x{0104}\x{00E0}\x{00E1}\x{00E2}\x{00E3}\x{00E4}\x{00E5}]/u','a', $str);
		$str = preg_replace('/[\x{00C7}\x{0106}\x{0108}\x{010A}\x{010C}]/u','C', $str);
		$str = preg_replace('/[\x{00E7}\x{0107}\x{0109}\x{010B}\x{010D}}]/u','c', $str);
		$str = preg_replace('/[\x{010E}\x{0110}]/u','D', $str);
		$str = preg_replace('/[\x{010F}\x{0111}]/u','d', $str);
		$str = preg_replace('/[\x{00C8}\x{00C9}\x{00CA}\x{00CB}\x{0112}\x{0114}\x{0116}\x{0118}\x{011A}]/u','E', $str);
		$str = preg_replace('/[\x{00E8}\x{00E9}\x{00EA}\x{00EB}\x{0113}\x{0115}\x{0117}\x{0119}\x{011B}]/u','e', $str);
		$str = preg_replace('/[\x{00CC}\x{00CD}\x{00CE}\x{00CF}\x{0128}\x{012A}\x{012C}\x{012E}\x{0130}]/u','I', $str);
		$str = preg_replace('/[\x{00EC}\x{00ED}\x{00EE}\x{00EF}\x{0129}\x{012B}\x{012D}\x{012F}\x{0131}]/u','i', $str);
		$str = preg_replace('/[\x{0142}\x{0141}\x{013E}\x{013A}]/u','l', $str);
		$str = preg_replace('/[\x{00F1}\x{0148}]/u','n', $str);
		$str = preg_replace('/[\x{00D2}\x{00D3}\x{00D4}\x{00D5}\x{00D6}\x{00D8}]/u','O', $str);
		$str = preg_replace('/[\x{00F2}\x{00F3}\x{00F4}\x{00F5}\x{00F6}\x{00F8}]/u','o', $str);
		$str = preg_replace('/[\x{0159}\x{0155}]/u','r', $str);
		$str = preg_replace('/[\x{015B}\x{015A}\x{0161}]/u','s', $str);
		$str = preg_replace('/[\x{00DF}]/u','ss', $str);
		$str = preg_replace('/[\x{0165}]/u','t', $str);
		$str = preg_replace('/[\x{00D9}\x{00DA}\x{00DB}\x{00DC}\x{016E}\x{0170}\x{0172}]/u','U', $str);
		$str = preg_replace('/[\x{00F9}\x{00FA}\x{00FB}\x{00FC}\x{016F}\x{0171}\x{0173}]/u','u', $str);
		$str = preg_replace('/[\x{00FD}\x{00FF}]/u','y', $str);
		$str = preg_replace('/[\x{017C}\x{017A}\x{017B}\x{0179}\x{017E}]/u','z', $str);
		$str = preg_replace('/[\x{00C6}]/u','AE', $str);
		$str = preg_replace('/[\x{00E6}]/u','ae', $str);
		$str = preg_replace('/[\x{0152}]/u','OE', $str);
		$str = preg_replace('/[\x{0153}]/u','oe', $str);
		$str = preg_replace('/[\x{0022}\x{0025}\x{0026}\x{0027}\x{00A1}\x{00A2}\x{00A3}\x{00A4}\x{00A5}\x{00A6}\x{00A7}\x{00A8}\x{00AA}\x{00AB}\x{00AC}\x{00AD}\x{00AE}\x{00AF}\x{00B0}\x{00B1}\x{00B2}\x{00B3}\x{00B4}\x{00B5}\x{00B6}\x{00B7}\x{00B8}\x{00BA}\x{00BB}\x{00BC}\x{00BD}\x{00BE}\x{00BF}]/u',' ', $str);
		return $str;
	}
}