<?php

declare(strict_types=1);

namespace App\Entity\Addressing;

use Doctrine\ORM\Mapping as ORM;
use Sylius\Component\Core\Model\Address as BaseAddress;

/**
 * @ORM\Entity
 * @ORM\Table(name="sylius_address")
 */
class Address extends BaseAddress
{
    //todo:ajout mobile

    public function __toString()
    {
        return $this->getFullName() . ' ' . $this->street . ' ' . $this->postcode . ' ' . $this->city . ' ' . $this->countryCode;
    }
}
