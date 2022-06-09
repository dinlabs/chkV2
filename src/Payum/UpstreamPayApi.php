<?php

declare(strict_types=1);

namespace App\Payum;

final class UpstreamPayApi
{
    /** @var string */
    private $clientId;
    
    /** @var string */
    private $clientSecret;
    
    /** @var string */
    private $apiKey;

    /** @var string */
    private $entityId;

    /** @var string */
    private $baseUrl;

    public function __construct(string $clientId, string $clientSecret, string $apiKey, string $entityId, string $baseUrl)
    {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->apiKey = $apiKey;
        $this->entityId = $entityId;
        $this->baseUrl = $baseUrl;
    }

    /**
     * Get the value of clientId
     */ 
    public function getClientId(): string
    {
        return $this->clientId;
    }

    /**
     * Get the value of clientSecret
     */ 
    public function getClientSecret(): string
    {
        return $this->clientSecret;
    }

    /**
     * Get the value of apiKey
     */ 
    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    /**
     * Get the value of entityId
     */ 
    public function getEntityId(): string
    {
        return $this->entityId;
    }

    /**
     * Get the value of baseUrl
     */ 
    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }
}