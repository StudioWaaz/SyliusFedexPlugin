<?php

declare(strict_types=1);

namespace Waaz\SyliusFedexPlugin\Factory;

use BitBag\SyliusShippingExportPlugin\Entity\ShippingGatewayInterface;

interface ShipperFactoryInterface
{
    /**
     * @return array<string, mixed>
     */
    public function createNew(ShippingGatewayInterface $shippingGateway): array;
}
