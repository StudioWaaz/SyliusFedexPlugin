<?php

declare(strict_types=1);

namespace Waaz\SyliusFedexPlugin\Factory;

use BitBag\SyliusShippingExportPlugin\Entity\ShippingGatewayInterface;
use Sylius\Component\Core\Model\ShipmentInterface;

interface ShipmentPayloadFactoryInterface
{
    /**
     * @return array<string, mixed>
     */
    public function createNew(
        ShippingGatewayInterface $shippingGateway,
        ShipmentInterface $shipment,
        string $weightUnit = 'KG',
    ): array;
}
