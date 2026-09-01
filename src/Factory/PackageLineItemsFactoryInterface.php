<?php

declare(strict_types=1);

namespace Waaz\SyliusFedexPlugin\Factory;

use Sylius\Component\Core\Model\ShipmentInterface;

interface PackageLineItemsFactoryInterface
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function createNew(ShipmentInterface $shipment, string $weightUnit = 'KG'): array;
}
