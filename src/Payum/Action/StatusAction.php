<?php

declare(strict_types=1);

namespace App\Payum\Action;

use Payum\Core\Action\ActionInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Request\GetStatusInterface;
use Sylius\Component\Core\Model\PaymentInterface as SyliusPaymentInterface;

final class StatusAction implements ActionInterface
{
    public function execute($request): void
    {
        error_log("Status->execute");
        RequestNotSupportedException::assertSupports($this, $request);

        $model = ArrayObject::ensureArrayObject($request->getModel());

        if ($model['error']) {
            $request->markFailed();
            return;
        }

        if (false == $model['status']) {
            //$request->markNew();

            return;
        }
        else {
            error_log("status : ".$model['status']);
        }

        //$request->markUnknown();
        
        // OU BIEN

        /* @var SyliusPaymentInterface $payment */
        $payment = $request->getFirstModel();
        $model = $payment->getDetails();

        error_log("model : ".$model['status']);

        if(200 === $model['status'])
        {
            $request->markCaptured();
            return;
        }

        if(400 === $model['status'])
        {
            $request->markFailed();
            return;
        } 
    }

    public function supports($request): bool
    {
        return
            $request instanceof GetStatusInterface &&
            $request->getModel() instanceof \ArrayAccess
            //$request->getModel() instanceof SyliusPaymentInterface
        ;
    }
}