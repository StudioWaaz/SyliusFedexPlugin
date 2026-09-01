<?php

declare(strict_types=1);

namespace Tests\Waaz\SyliusFedexPlugin\Unit\Factory;

use BitBag\SyliusShippingExportPlugin\Entity\ShippingGatewayInterface;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Core\Model\ShipmentInterface;
use Waaz\SyliusFedexPlugin\Factory\PackageLineItemsFactoryInterface;
use Waaz\SyliusFedexPlugin\Factory\RecipientFactoryInterface;
use Waaz\SyliusFedexPlugin\Factory\ShipmentPayloadFactory;
use Waaz\SyliusFedexPlugin\Factory\ShipperFactoryInterface;

final class ShipmentPayloadFactoryTest extends TestCase
{
    public function testCreateNew(): void
    {
        $shipperFactory = $this->createMock(ShipperFactoryInterface::class);
        $shipperFactory->method('createNew')->willReturn([
            'contact' => ['personName' => 'Shipper'],
            'address' => ['postalCode' => '75001'],
        ]);

        $recipientFactory = $this->createMock(RecipientFactoryInterface::class);
        $recipientFactory->method('createNew')->willReturn([
            'contact' => ['personName' => 'Recipient'],
            'address' => ['postalCode' => '75002'],
        ]);

        $packageFactory = $this->createMock(PackageLineItemsFactoryInterface::class);
        $packageFactory->method('createNew')->willReturn([
            ['weight' => ['units' => 'KG', 'value' => 2.0]],
        ]);

        $gateway = $this->createMock(ShippingGatewayInterface::class);
        $gateway->method('getConfig')->willReturn([
            'account_number' => '123456789',
            'service_type' => 'FEDEX_GROUND',
            'dropoff_type' => 'REGULAR_PICKUP',
            'packaging_type' => 'YOUR_PACKAGING',
            'label_image_type' => 'PDF',
            'label_stock_type' => 'PAPER_4X6',
        ]);

        $shipment = $this->createMock(ShipmentInterface::class);

        $factory = new ShipmentPayloadFactory($shipperFactory, $recipientFactory, $packageFactory);
        $payload = $factory->createNew($gateway, $shipment, 'KG');

        $this->assertEquals('LABEL', $payload['labelResponseOptions']);
        $this->assertEquals('123456789', $payload['accountNumber']['value']);
        $this->assertEquals('FEDEX_GROUND', $payload['requestedShipment']['serviceType']);
        $this->assertEquals('PDF', $payload['requestedShipment']['labelSpecification']['imageType']);
        $this->assertEquals('PAPER_4X6', $payload['requestedShipment']['labelSpecification']['labelStockType']);
        $this->assertCount(1, $payload['requestedShipment']['recipients']);
    }
}
