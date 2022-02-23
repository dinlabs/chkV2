<?php

declare(strict_types=1);

namespace App\Service;

class DpdHelper
{
    public function getPickupPoints(array $data)
	{
        if(!isset($data['address']))
        {
            //die('<div style="color:red; font-weight:700;">L\'adresse est obligatoire</div>');
            $data['address'] = '';
        }
        $address = $data['address'];
		$address = mb_convert_encoding(urldecode($address),'UTF-8');
		$address = self::stripAccents($address);
		
        if(!isset($data['zip']) || empty($data['zip']))
        {
            die('<div style="color:red; font-weight:700;">Le code postal est obligatoire</div>');
        }
        $zipcode = $data['zip'];
		$zipcode = trim(urldecode($zipcode));
		$zipcode = mb_convert_encoding($zipcode,'UTF-8');
		
        if(!isset($data['city']) || empty($data['city']))
        {
            die('<div style="color:red; font-weight:700;">La ville est obligatoire</div>');
        }
        $city = $data['city'];
		$city = mb_convert_encoding(urldecode($city),'UTF-8');
		$city = self::stripAccents($city);


        //Mage::getStoreConfig('carriers/dpdfrrelais');
        $serviceurl = 'http://mypudo.pickup-services.com/mypudo/mypudo.asmx?WSDL';
        $indentifier = 'EXA';
        $key = 'deecd7bc81b71fcc0e292b53e826c48f';

        //fallback IDs pour Chronopost WS
        //$indentifier = 'CHR';
        //$key = '75f6fe195dc88ceecbc0f8a2f70a8f3a';
        
        // Paramètres d'appel au WS MyPudo
        $variables = array( 'carrier'			=> $indentifier,
                            'key'				=> $key,
                            'address'			=> $address,
                            'zipCode'			=> $zipcode,
                            'city'				=> $city,
                            'countrycode'		=> 'FR',
                            'requestID'			=> '1234',
                            'request_id'		=> '1234',
                            'date_from'			=> date('d/m/Y'),
                            'max_pudo_number'	=> '',
                            'max_distance_search'=> '',
                            'weight'			=> '',
                            'category'			=> '',
                            'holiday_tolerant'	=> ''
        );

        // Message d'erreur si PHP_SOAP manquant
        if(!extension_loaded('soap'))
            echo '<div style="color:red; font-weight:700;">ATTENTION! L\'extension PHP SOAP n\'est pas activée sur ce serveur. Vous devez l\'activer pour utiliser le module DPD Relais.</div>';
        // Appel WS
        try
        {
            ini_set('default_socket_timeout', '3');
            $soappudo = new \SoapClient($serviceurl, [
                'connection_timeout' => 3,
                'cache_wsdl' => WSDL_CACHE_NONE, 
                'exceptions' => true
            ]);
            $GetPudoList = $soappudo->getPudoList($variables); // appel SOAP a l'applicatif GetPudoList
        }
        catch (\Exception $e)
        {
            echo '<div stlye="color:red; font-weight:bold;">Une erreur s\'est produite lors de la récupération des points-relais. Merci de réessayer.</div>';
            exit;
        }
        $doc_xml = new \SimpleXMLElement($GetPudoList->GetPudoListResult->any);  // parsage XML de la réponse SOAP
        $quality = (int)$doc_xml->attributes()->quality; // indice de qualité de la réponse SOAP
        
        if ($doc_xml->xpath('ERROR')) // si le webservice répond un code erreur, afficher un message d'indisponibilité
            echo '<div stlye="color:red; font-weight:bold;">Une erreur s\'est produite lors de la récupération des points-relais. Merci de réessayer.</div>';
        else
        {
            if ((int)$quality == 0)// Si la qualité de la réponse est 0, "merci d'indiquer une autre adresse"
                echo '<div stlye="color:red; font-weight:bold;">Il n\'y a aucun point-relais pour cette adresse. Merci de la modifier.</div>';
            else
            {
                $return = [];
                $allpudoitems = $doc_xml->xpath('PUDO_ITEMS'); // acceder a la balise pudo_items
                foreach($allpudoitems as $singlepudoitem) // eclatement des données contenues dans pudo_items
                {
                    $result = $singlepudoitem->xpath('PUDO_ITEM');
                    $i=0;
                    foreach($result as $result2)
                    {
                        // Nombre de points relais à afficher - max 10
                        if($i == 10) continue;
                        $i++;

                        $newPr = (object)[];
                        $newPr->inputValue = self::stripAccents((string)$result2->NAME).'|||'.self::stripAccents((string)$result2->ADDRESS1).'  '.self::stripAccents((string)$result2->ADDRESS2).'|||'.$result2->ZIPCODE.'|||'.self::stripAccents((string)$result2->CITY).'|||'.(string)$result2->PUDO_ID;
                        $newPr->nomEnseigne = $result2->NAME;
                        $newPr->adresse1 = $result2->ADDRESS1;
                        $newPr->adresse2 = $result2->ADDRESS2;
                        $newPr->adresse3 = $result2->ADDRESS3;
                        $newPr->codePostal = $result2->ZIPCODE;
                        $newPr->identifiantRelais = $result2->PUDO_ID;
                        $newPr->localite = $result2->CITY;
                        $newPr->latitude = (float)str_replace(',', '.', (string)$result2->LATITUDE);
                        $newPr->longitude = (float)str_replace(',', '.', (string)$result2->LONGITUDE);
                        $newPr->distance = $result2->DISTANCE/1000;//en km

                        $newPr->horairesOuvertureLundi = $newPr->horairesOuvertureMardi = $newPr->horairesOuvertureMercredi = $newPr->horairesOuvertureJeudi = $newPr->horairesOuvertureVendredi = $newPr->horairesOuvertureSamedi = $newPr->horairesOuvertureDimanche = '';
                        if(count($result2->OPENING_HOURS_ITEMS->OPENING_HOURS_ITEM)>0)
                        {
                            $listeHoraires = $result2->OPENING_HOURS_ITEMS->OPENING_HOURS_ITEM;
                            foreach($listeHoraires as $horaire) 
                            {
                                $horaire = (array)$horaire;
                                switch($horaire['DAY_ID']) {
                                    case '1' :
                                        if(!empty($newPr->horairesOuvertureLundi)) $newPr->horairesOuvertureLundi .= ' ';
                                        $newPr->horairesOuvertureLundi .= $horaire['START_TM'].'-'.$horaire['END_TM'];
                                        break;
                                    case '2' :
                                        if(!empty($newPr->horairesOuvertureMardi)) $newPr->horairesOuvertureMardi .= ' ';
                                        $newPr->horairesOuvertureMardi .= $horaire['START_TM'].'-'.$horaire['END_TM'];
                                        break;
                                    case '3' :
                                        if(!empty($newPr->horairesOuvertureMercredi)) $newPr->horairesOuvertureMercredi .= ' ';
                                        $newPr->horairesOuvertureMercredi .= $horaire['START_TM'].'-'.$horaire['END_TM'];
                                        break;
                                    case '4' :
                                        if(!empty($newPr->horairesOuvertureJeudi)) $newPr->horairesOuvertureJeudi .= ' ';
                                        $newPr->horairesOuvertureJeudi .= $horaire['START_TM'].'-'.$horaire['END_TM'];
                                        break;
                                    case '5' :
                                        if(!empty($newPr->horairesOuvertureVendredi)) $newPr->horairesOuvertureVendredi .= ' ';
                                        $newPr->horairesOuvertureVendredi .= $horaire['START_TM'].'-'.$horaire['END_TM'];
                                        break;
                                    case '6' :
                                        if(!empty($newPr->horairesOuvertureSamedi)) $newPr->horairesOuvertureSamedi .= ' ';
                                        $newPr->horairesOuvertureSamedi .= $horaire['START_TM'].'-'.$horaire['END_TM'];
                                        break;
                                    case '7' :
                                        if(!empty($newPr->horairesOuvertureDimanche)) $newPr->horairesOuvertureDimanche .= ' ';
                                        $newPr->horairesOuvertureDimanche .= $horaire['START_TM'].'-'.$horaire['END_TM'];
                                        break;
                                }
                            }
                        }
                        if(empty($newPr->horairesOuvertureLundi)) $newPr->horairesOuvertureLundi = 'Fermé';
                        if(empty($newPr->horairesOuvertureMardi)) $newPr->horairesOuvertureMardi = 'Fermé';
                        if(empty($newPr->horairesOuvertureMercredi)) $newPr->horairesOuvertureMercredi = 'Fermé';
                        if(empty($newPr->horairesOuvertureJeudi)) $newPr->horairesOuvertureJeudi = 'Fermé';
                        if(empty($newPr->horairesOuvertureVendredi)) $newPr->horairesOuvertureVendredi = 'Fermé';
                        if(empty($newPr->horairesOuvertureSamedi)) $newPr->horairesOuvertureSamedi = 'Fermé';
                        if(empty($newPr->horairesOuvertureDimanche)) $newPr->horairesOuvertureDimanche = 'Fermé';

                        $return[] = $newPr;
                    }
                }
                return $return;
            }
        }
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