<?php

declare(strict_types=1);

namespace App\Security;

//use App\Service\GinkoiaCustomerWs;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\DefaultAuthenticationSuccessHandler;

final class AuthenticationSuccessHandler extends DefaultAuthenticationSuccessHandler
{
    public function onAuthenticationSuccess(Request $request, TokenInterface $token): Response
    {
        error_log('MY onAuthenticationSuccess');
        $userEmail = $request->request->get('_username');
        //error_log('On va charger les infos de '.$userEmail.' depuis le WS pour mettre à jour ses infos sur le site.');

        

        if ($request->isXmlHttpRequest()) {
            return new JsonResponse(['success' => true, 'username' => $token->getUsername()]);
        }

        return parent::onAuthenticationSuccess($request, $token);
    }
}