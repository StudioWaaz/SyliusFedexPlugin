<?php

declare(strict_types=1);

namespace Tests\Waaz\SyliusFedexPlugin\Unit\EventListener;

use BitBag\SyliusShippingExportPlugin\Entity\ShippingExportInterface;
use BitBag\SyliusShippingExportPlugin\Entity\ShippingGatewayInterface;
use BitBag\SyliusShippingExportPlugin\Repository\ShippingExportRepository;
use PHPUnit\Framework\TestCase;
use Sylius\Bundle\ResourceBundle\Event\ResourceControllerEvent;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\ShipmentInterface;
use Symfony\Component\Filesystem\Filesystem;
use Waaz\SyliusFedexPlugin\Api\ShippingLabelFetcherInterface;
use Waaz\SyliusFedexPlugin\EventListener\ShippingExportEventListener;

final class ShippingExportEventListenerTest extends TestCase
{
    public function testExportShipment(): void
    {
        $filesystem = $this->createMock(Filesystem::class);
        $repository = $this->createMock(ShippingExportRepository::class);
        $fetcher = $this->createMock(ShippingLabelFetcherInterface::class);

        $gateway = $this->createMock(ShippingGatewayInterface::class);
        $gateway->method('getCode')->willReturn(ShippingExportEventListener::FEDEX_GATEWAY_CODE);

        $order = $this->createMock(OrderInterface::class);
        $order->method('getNumber')->willReturn('00000001');

        $shipment = $this->createMock(ShipmentInterface::class);
        $shipment->method('getId')->willReturn(10);
        $shipment->method('getOrder')->willReturn($order);

        $shippingExport = $this->createMock(ShippingExportInterface::class);
        $shippingExport->method('getShippingGateway')->willReturn($gateway);
        $shippingExport->method('getShipment')->willReturn($shipment);

        $fetcher->method('isSuccess')->willReturn(true);
        $fetcher->method('getLabelContent')->willReturn('DUMMY_PDF_CONTENT');
        $fetcher->method('getLabelExtension')->willReturn('pdf');
        $fetcher->method('getTrackingNumber')->willReturn('794611112222');

        $filesystem->expects($this->once())
            ->method('dumpFile')
            ->with('/tmp/labels/10_00000001.pdf', 'DUMMY_PDF_CONTENT');

        $shippingExport->expects($this->once())
            ->method('setLabelPath')
            ->with('/tmp/labels/10_00000001.pdf');

        $shippingExport->expects($this->once())
            ->method('setState')
            ->with(ShippingExportInterface::STATE_EXPORTED);

        $event = $this->createMock(ResourceControllerEvent::class);
        $event->method('getSubject')->willReturn($shippingExport);

        $listener = new ShippingExportEventListener($filesystem, $repository, '/tmp/labels', $fetcher);
        $listener->exportShipment($event);
    }
}
