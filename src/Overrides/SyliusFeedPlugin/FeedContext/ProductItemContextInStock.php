<?php

declare(strict_types=1);

namespace App\Overrides\SyliusFeedPlugin\FeedContext;

class ProductItemContextInStock extends ProductItemContext
{
    public function getFeedAllowNoStock(): bool
    {
        return false;
    }
}