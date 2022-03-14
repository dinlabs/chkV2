<?php

declare(strict_types=1);

namespace App\Controller;

use Sylius\Bundle\ResourceBundle\Controller\ResourceController;
use Sylius\Component\Resource\ResourceActions;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ProductController extends ResourceController
{
    public function indexAction(Request $request): Response
    {
        $configuration = $this->requestConfigurationFactory->create($this->metadata, $request);

        $this->isGrantedOr403($configuration, ResourceActions::INDEX);
        $products = $this->resourcesCollectionProvider->get($configuration, $this->repository);

        // some custom provider service to retrieve recommended products
        // test
        $service = $this->get('app.elasticsearch');

        $requestData = array_merge(
            [
                'name' => null, 
                'price' => ['min_price' => null, 'max_price' => null],
            ],
            $request->query->all(),
            ['slug' => $request->get('slug')]
        );
        $ret = $service->getProducts($requestData);
        $data = $ret['data'];
        $products = ['data' => $ret['products']];
        
        // test

        $this->eventDispatcher->dispatchMultiple(ResourceActions::INDEX, $configuration, $products);

        $template = $configuration->getTemplate(ResourceActions::INDEX . '.html');
        //$template = '@BitBagSyliusElasticsearchPlugin/Shop/Product/index.html.twig';
        //$template = 'chullanka/brand/view.html.twig';
        if ($configuration->isHtmlRequest()) {
            return $this->render($template, [
                'configuration' => $configuration,
                'metadata' => $this->metadata,
                'resources' => $products,
                $this->metadata->getPluralName() => $products,
            ]);
        }

        return $this->createRestView($configuration, $products);
    }
}