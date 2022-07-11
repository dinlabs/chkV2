<?php

namespace App\Grid;

use Sylius\Component\Grid\Event\GridDefinitionConverterEvent;

final class ShopAccountOrdersGridListener
{
    public function editFields(GridDefinitionConverterEvent $event): void
    {
        $grid = $event->getGrid();
        $dateField = $grid->getField('checkoutCompletedAt');
        $dateField->setOptions(['format' => 'd/m/Y']);
    }
}