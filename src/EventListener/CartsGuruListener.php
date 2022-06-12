<?php

namespace App\EventListener;

use App\Entity\Customer\Customer;
use App\Entity\Order\Order;
use App\Entity\Order\OrderItem;
use App\Overrides\SyliusFeedPlugin\FeedContext\ProductItemContext;
use App\Service\CartsGuruService;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Psr\Log\LoggerInterface;
use Sylius\Component\Core\OrderCheckoutStates;
use Sylius\Component\Customer\Model\CustomerInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class CartsGuruListener
{
    private LoggerInterface $logger;

    private TranslatorInterface $translator;

    private CartsGuruService $cartsGuruService;

    private CacheManager $cacheManager;

    private string $baseUri;

    private UrlGeneratorInterface $router;

    private bool $cartsGuruActive;

    public function __construct(LoggerInterface $logger, TranslatorInterface $translator,
                                CartsGuruService $cartsGuruService, CacheManager $cacheManager,
                                UrlGeneratorInterface $router, string $baseUri,
                                bool $cartsGuruActive)
    {
        $this->logger = $logger;
        $this->translator = $translator;
        $this->cartsGuruService = $cartsGuruService;
        $this->cacheManager = $cacheManager;
        $this->router = $router;
        $this->baseUri = $baseUri;
        $this->cartsGuruActive = $cartsGuruActive;
    }

    public function onOrderPostCreate(GenericEvent $event)
    {
        if (!$event->getSubject() instanceof Order) {
            return;
        }

        if (!$this->cartsGuruActive) {
            $this->logger->info('CartsGuru is disabled');
            return;
        }

        $order = $event->getSubject();

        $this->sendCartsGuru($order, __FUNCTION__);
    }

    public function onOrderPostUpdate(GenericEvent $event)
    {
        if (!$event->getSubject() instanceof Order) {
            return;
        }

        if (!$this->cartsGuruActive) {
            $this->logger->info('CartsGuru is disabled');
            return;
        }

        $order = $event->getSubject();

        $this->sendCartsGuru($order, __FUNCTION__);
    }


    public function onOrderItemPreRemove(GenericEvent $event)
    {
        if (!$event->getSubject() instanceof OrderItem) {
            return;
        }

        if (!$this->cartsGuruActive) {
            $this->logger->info('CartsGuru is disabled');
            return;
        }

        $order = $event->getSubject()->getOrder();
        // we must remove the item manually because we use pre_remove event
        // if we use post_remove event, the subject is null impossible to use
        $order->removeItem($event->getSubject());

        $this->sendCartsGuru($order, __FUNCTION__);

        // re-add the item to avoid error :
        // Call to a member function getItems() on null
        $order->addItem($event->getSubject());
    }

    private function sendCartsGuru($order, $function)
    {
        try {
            $isCart = ($order->getState() === OrderCheckoutStates::STATE_CART) ? true : false;

            /** OrderItem $item */
            $items = [];
            foreach ($order->getItems() as $orderItem) {
                $product = $orderItem->getVariant()->getProduct();
                $mainImage = $product->getOrderedImages()?->get(0)->getPath();

                $item = [
                    'id' => $product->getId(),
                    'label' => $orderItem->getProductName(),
                    'quantity' => $orderItem->getQuantity(),
                    'totalATI' => $orderItem->getTotal() / 100,
                    'totalET' => ($orderItem->getTotal() - $orderItem->getAdjustmentsTotal()) / 100,
                    'url' => $this->baseUri. $this->router->generate('sylius_shop_partial_product_show_by_slug', ['slug' => $product->getSlug()]),
                    'imageUrl' => ($mainImage !== null) ? $this->cacheManager->getBrowserPath($mainImage, 'sylius_shop_product_large_thumbnail') : '',
                    'universe' => ($product->getMainTaxon() !== null) ? $product->getMainTaxon()->getName() : '',
                    'category' => ($product->getHighestTaxon() !== null) ? $product->getHighestTaxon()->getName() : '',
                ];

                if ($isCart) {
                    $item['custom'] = [];
                }

                $items[] = $item;
            }

            $data = [
                'siteId' => 'test',
                'id' => $order->getId(),
                'totalATI' => $order->getTotal() / 100,
                'totalET' => $order->getItemsTotal() / 100,
                'currency' => 'EUR',
                'accountId' => ($order->getCustomer()) ? $order->getCustomer()->getEmail() : '',
                'ip' => $this->getCustomerIp(),
                'recoverUrl' => $this->baseUri. $this->router->generate('sylius_shop_cart_summary'),
                'civility' => $this->getCustomerCivility($order->getCustomer()),
                'lastname' => ($order->getCustomer()) ? $order->getCustomer()->getLastName() : '',
                'firstname' => ($order->getCustomer()) ? $order->getCustomer()->getLastName() : '',
                'email' => ($order->getCustomer()) ? $order->getCustomer()->getEmail() : '',
                'homePhoneNumber' => '',
                'mobilePhoneNumber' => '',
                'phoneNumber' => '',
                'countryCode' => '',
                'language' => '',
                'custom' => [],
                'buyerAcceptsMarketing' => ($order->getCustomer()) ? $order->getCustomer()->isSubscribedToNewsletter() : false,
                'items' => $items
            ];

            if (!$isCart) {
                $paymentMethods = '';
                $state = $this->translator->trans('sylius.ui.'. $order->getState());
                $state = ($state !== 'sylius.ui.'. $order->getState()) ? $state : '';

                foreach ($order->getPayments() as $key => $payment) {
                    $paymentMethods .= $payment->getMethod()->getName();
                    $paymentMethods .= (($key + 1) < count($order->getPayments())) ? ', ' : '';
                }

                $data = array_merge($data, [
                    'cartId' => $order->getId(),
                    'paymentMethod' => $paymentMethods,
                    'state' => $state
                ]);
            }

            if ($order->getState() === OrderCheckoutStates::STATE_CART) {
                $response = $this->cartsGuruService->trackCart($data);
            } else {
                $response = $this->cartsGuruService->trackOrder($data);
            }

            $responseInfo = 'Event Origin: '. $function. ', ';
            $responseInfo .= 'Body: '. json_encode($data). ', ';
            $responseInfo .= 'Reponse: '. $response->decoded_response. ', ';
            $responseInfo .= 'Response Error: '. $response->error;
            $this->logger->info($responseInfo);
        } catch (\Throwable $throwable) {
            $msg = "Error: {$throwable->getMessage()}, ";
            $msg .= "line: {$throwable->getLine()}, ";
            $msg .= "file: {$throwable->getFile()}";

            $this->logger->error($msg);
        }
    }

    private function getCustomerCivility(?Customer $customer)
    {
        $civility = '';
        $civilities = [
            CustomerInterface::MALE_GENDER => 'mister',
            CustomerInterface::FEMALE_GENDER => 'miss'
        ];

        if ($customer !== null && array_key_exists($customer->getGender(), $civilities)) {
            return $civilities[$customer->getGender()];
        }

        return $civility;
    }

    private function getCustomerIp()
    {
        $ip = '';
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        return $ip;
    }
}