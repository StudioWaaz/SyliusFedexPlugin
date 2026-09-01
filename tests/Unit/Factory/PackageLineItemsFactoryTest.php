<?php

declare(strict_types=1);

namespace Tests\Waaz\SyliusFedexPlugin\Unit\Factory;

use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Core\Model\ShipmentInterface;
use Sylius\Component\Shipping\Model\ShipmentUnitInterface;
use Waaz\SyliusFedexPlugin\Factory\PackageLineItemsFactory;

final class PackageLineItemsFactoryTest extends TestCase
{
    public function testCreateNewWithDefaultWeight(): void
    {
        $shipment = $this->createMock(ShipmentInterface::class);
        $shipment->method('getUnits')->willReturn(new ArrayCollection());

        $factory = new PackageLineItemsFactory();
        $result = $factory->createNew($shipment, 'KG');

        $this->assertCount(1, $result);
        $this->assertEquals('KG', $result[0]['weight']['units']);
        $this->assertEquals(1.0, $result[0]['weight']['value']);
    }

    public function testCreateNewWithItemWeights(): void
    {
        $shippable = $this->createMock(\Sylius\Component\Shipping\Model\ShippableInterface::class);
        $shippable->method('getShippingWeight')->willReturn(2.5);

        $unit = $this->createMock(ShipmentUnitInterface::class);
        $unit->method('getShippable')->willReturn($shippable);

        $shipment = $this->createMock(ShipmentInterface::class);
        $shipment->method('getUnits')->willReturn(new ArrayCollection([$unit]));

        $factory = new PackageLineItemsFactory();
        $result = $factory->createNew($shipment, 'KG');

        $this->assertCount(1, $result);
        $this->assertEquals(2.5, $result[0]['weight']['value']);
    }
}
