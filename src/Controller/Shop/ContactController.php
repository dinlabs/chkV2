<?php

declare(strict_types=1);

namespace App\Controller\Shop;

use App\Form\Type\ContactType;
use Sylius\Bundle\ShopBundle\EmailManager\ContactEmailManagerInterface;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Customer\Context\CustomerContextInterface;
use Sylius\Component\Locale\Context\LocaleContextInterface;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;
use Webmozart\Assert\Assert;

final class ContactController
{
    /** @var RouterInterface */
    private $router;

    /** @var FormFactoryInterface */
    private $formFactory;

    /** @var EngineInterface|Environment */
    private $templatingEngine;

    /** @var ChannelContextInterface */
    private $channelContext;

    /** @var CustomerContextInterface */
    private $customerContext;

    /** @var LocaleContextInterface */
    private $localeContext;

    /** @var ContactEmailManagerInterface */
    private $contactEmailManager;

    /**
     * @param EngineInterface|Environment $templatingEngine
     */
    public function __construct(
        RouterInterface $router,
        FormFactoryInterface $formFactory,
        object $templatingEngine,
        ChannelContextInterface $channelContext,
        CustomerContextInterface $customerContext,
        LocaleContextInterface $localeContext,
        ContactEmailManagerInterface $contactEmailManager
    ) {
        $this->router = $router;
        $this->formFactory = $formFactory;
        $this->templatingEngine = $templatingEngine;
        $this->channelContext = $channelContext;
        $this->customerContext = $customerContext;
        $this->localeContext = $localeContext;
        $this->contactEmailManager = $contactEmailManager;
    }

    public function requestAction(Request $request): Response
    {
        $formType = $this->getSyliusAttribute($request, 'form', ContactType::class);
        $form = $this->formFactory->create($formType, null, $this->getFormOptions());

        if ($request->isMethod('POST') && $form->handleRequest($request)->isValid()) {
            $data = $form->getData();

            $channel = $this->channelContext->getChannel();

            /** @var ChannelInterface $channel */
            Assert::isInstanceOf($channel, ChannelInterface::class);

            $contactEmail = $channel->getContactEmail();

            switch($data['subject'])
            {
                case 'produits':
                case 'discount':
                    $contactEmail = 'contact.vente@chullanka.com';
                    break;
                
                case 'sav':
                    $contactEmail = 'contact.sav@chullanka.com';
                    break;

                case 'sponsor':
                    $contactEmail = 'sponsoring@chullanka.com';
                    break;
                    
                case 'magasins':
                case 'site':
                case 'autre':
                    $contactEmail = 'contact@chullanka.com';
                    break;
            }

            if (null === $contactEmail) {
                $errorMessage = $this->getSyliusAttribute(
                    $request,
                    'error_flash',
                    'sylius.contact.request_error'
                );

                /** @var FlashBagInterface $flashBag */
                $flashBag = $request->getSession()->getBag('flashes');
                $flashBag->add('error', $errorMessage);

                return new RedirectResponse($request->headers->get('referer'));
            }

            $localeCode = $this->localeContext->getLocaleCode();
            $this->contactEmailManager->sendContactRequest($data, [$contactEmail], $channel, $localeCode);

            $successMessage = $this->getSyliusAttribute(
                $request,
                'success_flash',
                'sylius.contact.request_success'
            );

            /** @var FlashBagInterface $flashBag */
            $flashBag = $request->getSession()->getBag('flashes');
            $flashBag->add('success', $successMessage);

            $redirectRoute = $this->getSyliusAttribute($request, 'redirect', 'referer');

            return new RedirectResponse($this->router->generate($redirectRoute));
        }

        $template = $this->getSyliusAttribute($request, 'template', '@SyliusShop/Contact/request.html.twig');

        return new Response($this->templatingEngine->render($template, ['form' => $form->createView()]));
    }

    private function getSyliusAttribute(Request $request, string $attributeName, ?string $default): ?string
    {
        $attributes = $request->attributes->get('_sylius');

        return $attributes[$attributeName] ?? $default;
    }

    private function getFormOptions(): array
    {
        $customer = $this->customerContext->getCustomer();

        if (null === $customer) {
            return [];
        }

        return ['email' => $customer->getEmail()];
    }
}
