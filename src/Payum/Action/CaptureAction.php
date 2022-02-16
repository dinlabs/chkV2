<?php

declare(strict_types=1);

namespace App\Payum\Action;

use App\Payum\UpstreamPayApi;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Exception\UnsupportedApiException;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Request\Capture;
use Sylius\Component\Core\Model\PaymentInterface as SyliusPaymentInterface;

final class CaptureAction implements ActionInterface, ApiAwareInterface
{
    use GatewayAwareTrait;
    
    /** @var Client */
    private $client;
    /** @var UpstreamPayApi */
    private $api;
    /** @var string */
    private $apiUrl;


    public function __construct(Client $client)
    {
        $this->client = $client;
        //$this->apiUrl = 'https://sylius-payment.free.beeceptor.com';
        $this->apiUrl = 'http://www.chullanka2.git.yl/fakeapi.php';
    }

    public function execute($request): void
    {
        error_log("Capture->execute");
        RequestNotSupportedException::assertSupports($this, $request);

        /** @var SyliusPaymentInterface $payment */
        $payment = $request->getModel();

        try {
            error_log("call ".$this->apiUrl);
            $response = $this->client->request('POST', $this->apiUrl, [
                'body' => json_encode([
                    'price' => $payment->getAmount(),
                    'currency' => $payment->getCurrencyCode(),
                    'api_key' => $this->api->getApiKey(),
                ])
            ]);
        } catch(RequestException $exception) {
            $response = $exception->getResponse();
        } finally {
            $payment->setDetails(['status' => $response->getStatusCode()]);
        }
    }

    public function supports($request): bool
    {
        return
            $request instanceof Capture &&
            $request->getModel() instanceof SyliusPaymentInterface
        ;
    }

    public function setApi($api): void
    {
        if(!$api instanceof UpstreamPayApi)
        {
            throw new UnsupportedApiException('Not supported. Expected an instance of ' . SyliusApi::class);
        }
        $this->api = $api;
    }
}