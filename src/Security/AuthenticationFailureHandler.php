<?php

declare(strict_types=1);

namespace App\Security;

use App\Service\GinkoiaCustomerWs;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authentication\DefaultAuthenticationFailureHandler;
use Symfony\Component\Security\Http\HttpUtils;

final class AuthenticationFailureHandler extends DefaultAuthenticationFailureHandler
{
    protected $httpKernel;
    protected $httpUtils;
    protected $logger;
    protected $options;
    protected $defaultOptions = [
        'failure_path' => null,
        'failure_forward' => false,
        'login_path' => '/login',
        'failure_path_parameter' => '_failure_path',
    ];
    private $ginkoiaCustomerWs;

    public function __construct(HttpKernelInterface $httpKernel, HttpUtils $httpUtils, GinkoiaCustomerWs $ginkoiaCustomerWs, array $options = [], LoggerInterface $logger = null)
    {
        $this->httpKernel = $httpKernel;
        $this->httpUtils = $httpUtils;
        $this->logger = $logger;
        $this->setOptions($options);
        $this->ginkoiaCustomerWs = $ginkoiaCustomerWs;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        error_log("My onAuthenticationFailure");
        $email = $request->request->get('_username');
        $return = $this->ginkoiaCustomerWs->getCustomerInfos($email);
        
        error_log($return?"ok":"ko");

        if ($request->isXmlHttpRequest()) {
            return new JsonResponse(['success' => false, 'message' => $exception->getMessageKey()], 401);
        }

        return parent::onAuthenticationFailure($request, $exception);
    }
}
