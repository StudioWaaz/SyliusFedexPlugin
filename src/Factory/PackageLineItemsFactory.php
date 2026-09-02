<?php

declare(strict_types=1);

namespace Waaz\SyliusFedexPlugin\Factory;

use Sylius\Component\Core\Model\ShipmentInterface;

final class PackageLineItemsFactory implements PackageLineItemsFactoryInterface
{
    public function createNew(ShipmentInterface $shipment, string $weightUnit = 'KG'): array
    {
        $weight = $this->calculateWeight($shipment, $weightUnit);

        return [
            [
                'weight' => [
                    'units' => strtoupper($weightUnit),
                    'value' => max(0.1, round($weight, 2)),
                ],
            ],
        ];
    }

    private function calculateWeight(ShipmentInterface $shipment, string $weightUnit): float
    {
        $totalWeight = 0.0;

        foreach ($shipment->getUnits() as $unit) {
            $shippable = $unit->getShippable();
            if ($shippable !== null) {
                $itemWeight = (float) $shippable->getShippingWeight();
                $totalWeight += $itemWeight;
            }
        }

        if ($totalWeight <= 0.0) {
            return 1.0;
        }

        // If unit in Sylius was in grams and FedEx requires KG, convert
        if (strtoupper($weightUnit) === 'KG' && $totalWeight > 100) {
            $totalWeight = $totalWeight / 1000.0;
        }

        return $totalWeight;
    }
}
