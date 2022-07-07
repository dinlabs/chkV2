<?php

declare(strict_types=1);

namespace App\Context;
//namespace Sylius\Bundle\CoreBundle\Context;

use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Channel\Context\ChannelNotFoundException;
use Sylius\Component\Core\Repository\OrderRepositoryInterface;
use Sylius\Component\Core\Storage\CartStorageInterface;
use Sylius\Component\Customer\Context\CustomerContextInterface;
use Sylius\Component\Order\Context\CartContextInterface;
use Sylius\Component\Order\Context\CartNotFoundException;
use Sylius\Component\Order\Model\OrderInterface;

final class MergeSessionWithCustomerAndChannelCartContext implements CartContextInterface
{
    private CustomerContextInterface $customerContext;

    private CartStorageInterface $cartStorage;

    private ChannelContextInterface $channelContext;

    private OrderRepositoryInterface $orderRepository;

    public function __construct(
        CustomerContextInterface $customerContext,
        CartStorageInterface $cartStorage,
        ChannelContextInterface $channelContext,
        OrderRepositoryInterface $orderRepository
    ) {
        $this->customerContext = $customerContext;
        $this->cartStorage = $cartStorage;
        $this->channelContext = $channelContext;
        $this->orderRepository = $orderRepository;
    }

    public function getCart(): OrderInterface
    {
        try {
            $channel = $this->channelContext->getChannel();
        } catch (ChannelNotFoundException $exception) {
            throw new CartNotFoundException('Sylius was not able to find the cart, as there is no current channel.');
        }

        $customerCart = null;
        if($customer = $this->customerContext->getCustomer())
        {
            $customerCart = $this->orderRepository->findLatestNotEmptyCartByChannelAndCustomer($channel, $customer);
        }

        $sessionCart = $this->cartStorage->getForChannel($channel);

        if($customerCart && $sessionCart)
        {
            foreach($sessionCart->getItems() as $item)
            {
                $item->setOrder($customerCart);
            }
            $cart = $customerCart;
        }
        elseif($sessionCart) 
        {
            $cart = $sessionCart;
        }
        elseif($customerCart) 
        {
            $cart = $customerCart;
        }
        else
        {
            throw new CartNotFoundException('Sylius was not able to find the cart in session');
        }

        return $cart;
    }
}
