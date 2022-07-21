<?php

declare(strict_types=1);

namespace App\Entity\Shipping;

use Doctrine\ORM\Mapping as ORM;
use Sylius\Component\Core\Model\Shipment as BaseShipment;

/**
 * @ORM\Entity
 * @ORM\Table(name="sylius_shipment")
 */
class Shipment extends BaseShipment
{

    public function getTrackingUrl(): String
    {
        if(!empty($this->tracking))
        {
            $shipping_method = $this->getMethod()->getCode();
            $split_ship = explode('_', $shipping_method);
            $shipping_method_type = $split_ship[0];
            $shipping_method_speed = $split_ship[1];
            
            if($shipping_method_type == 'home')
            {
                if($shipping_method_speed == 'standart')
                {
                    if(strlen($this->tracking) > 15)
                    {
                        return 'https://trace.dpd.fr/fr/trace/' . $this->tracking;
                    }
                    else
                    {
                        return 'https://www.laposte.fr/outils/suivre-vos-envois?code=' . $this->tracking;
                    }
                }
                elseif($shipping_method_speed == 'express')
                {
                    return 'https://www.chronopost.fr/tracking-no-cms/suivi-page?listeNumerosLT=' . $this->tracking;
                }
            }
            elseif($shipping_method_type == 'pickup')
            {
                if($shipping_method_speed == 'standart')
                {
                    if(strlen($this->tracking) > 15)
                    {
                        return 'https://trace.dpd.fr/fr/trace/' . $this->tracking;
                    }
                    else
                    {
                        return 'https://www.laposte.fr/outils/suivre-vos-envois?code=' . $this->tracking;
                    }
                }
                elseif($shipping_method_speed == 'express')
                {
                    return 'https://www.chronopost.fr/tracking-no-cms/suivi-page?listeNumerosLT=' . $this->tracking;
                }
            }
        }
        return '';//default
    }
}
