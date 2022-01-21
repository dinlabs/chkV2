<?php

declare(strict_types=1);

namespace App\Service;

class DpdHelper
{
    private $projectDir;

    public function __construct(string $projectDir)
    {
        $this->projectDir = $projectDir;
        $this->exportDir = $this->projectDir . '/var/exports/';
    }

    public function getPickupPoints(array $data)
	{
        if(!isset($data['address']))
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
		$city = self::stripAccents($city);


        //Mage::getStoreConfig('carriers/dpdfrrelais/serviceurl');
        $serviceurl = 'http://mypudo.pickup-services.com/mypudo/mypudo.asmx?WSDL';

        //Mage::getStoreConfig('carriers/dpdfrrelais/indentifier');
        $firmid = 'EXA';

        //Mage::getStoreConfig('carriers/dpdfrrelais/key');
        $key = 'deecd7bc81b71fcc0e292b53e826c48f';
        
        // Paramètres d'appel au WS MyPudo
        $variables = array( 'carrier'			=> $firmid,
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
                $filename = 'PICKUPS.xml';
                $file = $this->exportDir . $filename;
                //file_put_contents($file, $doc_xml->saveXML(), FILE_APPEND);

                $allpudoitems = $doc_xml->xpath('PUDO_ITEMS'); // acceder a la balise pudo_items
                foreach($allpudoitems as $singlepudoitem) // eclatement des données contenues dans pudo_items
                {
                    $result = $singlepudoitem->xpath('PUDO_ITEM');
                    $i=0;
                    foreach($result as $result2)
                    {
                        $offset = $i;
                        
                        $LATITUDE = (float)str_replace(",",".",(string)$result2->LATITUDE);
                        $LONGITUDE = (float)str_replace(",",".",(string)$result2->LONGITUDE);

                        $html = '
                        <div class="pickupElement">
                            <input type="radio" id="pickup_point'.$offset.'" name="pickup_point" value="'.self::stripAccents((string)$result2->NAME).'|||'.self::stripAccents((string)$result2->ADDRESS1).'  '.self::stripAccents((string)$result2->ADDRESS2).'|||'.$result2->ZIPCODE.'|||'.self::stripAccents((string)$result2->CITY).'|||'.(string)$result2->PUDO_ID.'">
                            <label class="small" for="pickup_point'.$offset.'"><strong>'.self::stripAccents((string)$result2->NAME).'</strong></label><br/>'.self::stripAccents((string)$result2->ADDRESS1).', '.$result2->ZIPCODE.' '.self::stripAccents((string)$result2->CITY).'<br>
                            <button class="pictoLink fold" data-on="Replier" data-off="Détails">Détails</button>
                            ';
                            
                        $days=array(1=>'monday',2=>'tuesday',3=>'wednesday',4=>'thursday',5=>'friday',6=>'saturday',7=>'sunday');
                        $point=array();
                        $item=(array)$result2;
                        
                        if(count($item['OPENING_HOURS_ITEMS']->OPENING_HOURS_ITEM)>0)
                        {
                            foreach($item['OPENING_HOURS_ITEMS']->OPENING_HOURS_ITEM as $k=>$oh_item)
                            {
                                $oh_item=(array)$oh_item;
                                $point[$days[$oh_item['DAY_ID']]][]=$oh_item['START_TM'].' - '.$oh_item['END_TM'];
                            }
                        }
                        
                        if(empty($point['monday'])){$h1 = 'Fermé';}
                        else{if(empty($point['monday'][1])){$h1 = $point['monday'][0];}
                                else{$h1 = $point['monday'][0].' & '.$point['monday'][1];}}
                                
                        if(empty($point['tuesday'])){$h2 = 'Fermé';}
                            else{if(empty($point['tuesday'][1])){$h2 = $point['tuesday'][0];}
                                else{$h2 = $point['tuesday'][0].' & '.$point['tuesday'][1];}}
                                
                        if(empty($point['wednesday'])){$h3 = 'Fermé';}
                            else{if(empty($point['wednesday'][1])){$h3 = $point['wednesday'][0];}
                                else{$h3 = $point['wednesday'][0].' & '.$point['wednesday'][1];}}
                                
                        if(empty($point['thursday'])){$h4 = 'Fermé';}
                            else{if(empty($point['thursday'][1])){$h4 = $point['thursday'][0];}
                                else{$h4 = $point['thursday'][0].' & '.$point['thursday'][1];}}
                                
                        if(empty($point['friday'])){$h5 = 'Fermé';}
                            else{if(empty($point['friday'][1])){$h5 = $point['friday'][0];}
                                else{$h5 = $point['friday'][0].' & '.$point['friday'][1];}}
                                
                        if(empty($point['saturday'])){$h6 = 'Fermé';}
                            else{if(empty($point['saturday'][1])){$h6 = $point['saturday'][0];}
                                else{$h6 = $point['saturday'][0].' & '.$point['saturday'][1];}}
                                
                        if(empty($point['sunday'])){$h7 = 'Fermé';}
                            else{if(empty($point['sunday'][1])){$h7 = $point['sunday'][0];}
                                else{$h7 = $point['sunday'][0].' & '.$point['sunday'][1];}}
                        
                        $html .= '<div id="relaydetail'.$offset.'" class="foldable"><div class="relayDetails">
                                    <strong>'.$result2->NAME.'</strong></br>
                                    '.$result2->ADDRESS1.'</br>';
                                    if (!empty($result2->ADDRESS2)) $html .= $result2->ADDRESS2.'</br>';
                                    $html .= $result2->ZIPCODE.'  '.$result2->CITY.'<br/>';
                                    if (!empty($result2->LOCAL_HINT)) $html .= '<p>info  :  '.$result2->LOCAL_HINT.'</p>';

                        $html .= '<div class="boxhoraires">
                                    <div>Horaires</div>
                                    <p><strong>Lundi : </strong>'.$h1.'</p>
                                    <p><strong>Mardi : </strong>'.$h2.'</p>
                                    <p><strong>Mercreci : </strong>'.$h3.'</p>
                                    <p><strong>Jeudi : </strong>'.$h4.'</p>
                                    <p><strong>Vendredi : </strong>'.$h5.'</p>
                                    <p><strong>Samedi : </strong>'.$h6.'</p>
                                    <p><strong>Dimanche : </strong>'.$h7.'</p>
                                </div>';

                        $html .= '<div class="boxinfos">
                                    <div>Plus d\'infos</div>
                                    <div><h5>Distance en KM  :  </h5><strong>'.sprintf("%01.2f", $result2->DISTANCE/1000).' km </strong></div>
                                    <div><h5>Identifiant du relais :  </h5><strong>'.(string)$result2->PUDO_ID.'</strong></div>';
                                if (count($result2->HOLIDAY_ITEMS->HOLIDAY_ITEM) > 0)
                                {
                                    foreach ($result2->HOLIDAY_ITEMS->HOLIDAY_ITEM as $holiday_item)
                                    {
                                        $holiday_item = (array)$holiday_item;
                                        $html .= '<div><h4>Période de fermeture : </h4> '.$holiday_item['START_DTM'].' - '.$holiday_item['END_DTM'].'</div>';
                                    }
                                }	
                            $html .= '</div>';
                        
                        $html .= '</div></div></div>'; // relaydetail
                        echo $html;
                        
                        $i++;
                        $hd1 = $hd2 = $hd3 = $hd4 = $hd5 = $hd6 = $hd7 = $h1 = $h2 = $h3 = $h4 = $h5 = $h6 = $h7 = null;
                        if($i == 10) // Nombre de points relais à afficher - max 10
                            exit();
                    }
                }
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