<?php

declare(strict_types=1);

namespace App\Payum;

use App\Payum\Action\CaptureAction;
use App\Payum\Action\StatusAction;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\GatewayFactory;

final class UpstreamPayGatewayFactory extends GatewayFactory
{
    protected function populateConfig(ArrayObject $config): void
    {
        $config->defaults([
            'payum.factory_name' => 'upstream_pay',
            'payum.factory_title' => 'UpStream Pay',
            //'payum.action.capture' => new CaptureAction(),
            'payum.action.status' => new StatusAction(),
        ]);
        if(false !== (bool) $config['payum.api']) return;

        $config['payum.api'] = function(ArrayObject $config)
        {
            return new UpstreamPayApi($config['client_id'], $config['client_secret'], $config['api_key'], $config['entity_id'], $config['base_url']);
        };
    }
}