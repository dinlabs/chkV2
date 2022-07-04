<?php

declare(strict_types=1);

namespace App\Component\Core\Customer;

use Sylius\Component\Core\Model\AddressInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Customer\CustomerAddressAdderInterface;
use Sylius\Component\Core\Customer\OrderAddressesSaverInterface;

final class CustomerOrderAddressesSaver implements OrderAddressesSaverInterface
{
    private CustomerAddressAdderInterface $addressAdder;

    public function __construct(CustomerAddressAdderInterface $addressAdder)
    {
        $this->addressAdder = $addressAdder;
    }

    public function saveAddresses(OrderInterface $order): void
    {
        /** @var CustomerInterface $customer */
        $customer = $order->getCustomer();
        if (null === $customer->getUser()) {
            return;
        }

        $this->addAddress($customer, $order->getBillingAddress());

        //test shipment_method
        if($shipment = $order->getShipments())
        {
            $shipping_method = $shipment->first()->getMethod()->getCode();
            $split_ship = explode('_', $shipping_method);

            // on n'enregistre pas les adresses des points-relais ou magasins
            if(in_array($split_ship[0], ['pickup', 'store'])) return;
        }
        $this->addAddress($customer, $order->getShippingAddress());
    }

    private function addAddress(CustomerInterface $customer, ?AddressInterface $address): void
    {
        if (null !== $address) {
            $this->addressAdder->add($customer, clone $address);
        }
    }
}
