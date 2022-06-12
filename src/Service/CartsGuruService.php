<?php

namespace App\Service;

use CartsGuru\CartsGuru\CartsGuru as CartsGuru;

class CartsGuruService extends CartsGuru
{
    public function __construct($params)
    {
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
