<?php

namespace App\Service;

use App\Entity\Chullanka\Parameter;
use CartsGuru\CartsGuru\CartsGuru as CartsGuru;

class CartsGuruService extends CartsGuru
{
    public function __construct($params)
    {
        $em = $params['entity_manager'];
        $cartsGuruAuths = $em->getRepository(Parameter::class)->getCartsGuruAuths([
            'cartsguru-site-id',
            'cartsguru-auth-key'
        ]);

        if (count($cartsGuruAuths) == 2) {
            $params = array_merge($params, [
                'site_id' => $cartsGuruAuths['cartsguru-site-id'],
                'auth_key' => $cartsGuruAuths['cartsguru-auth-key']
            ]);
        } else {
            // if no parameters found, do not send to carts guru
            return;
        }

        unset($params['entity_manager']);

        parent::__construct($params);
    }

    public function adaptCart($item)
    {
        return $item;
    }

    public function adaptOrder($item)
    {
        return $item;
    }

    public function adaptProduct($item)
    {
        return $item;
    }
}
