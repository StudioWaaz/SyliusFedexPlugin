<?php

declare(strict_types=1);

namespace Waaz\SyliusFedexPlugin\Factory;

use Sylius\Component\Core\Model\ShipmentInterface;

interface RecipientFactoryInterface
{
    /**
     * @return array<string, mixed>
     */
    public function createNew(ShipmentInterface $shipment): array;
}
