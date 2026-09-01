<?php

declare(strict_types=1);

namespace Tests\Waaz\SyliusFedexPlugin\Unit\Api;

use BitBag\SyliusShippingExportPlugin\Entity\ShippingGatewayInterface;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Core\Model\ShipmentInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Waaz\SyliusFedexPlugin\Api\FedexClientInterface;
use Waaz\SyliusFedexPlugin\Api\ShippingLabelFetcher;
use Waaz\SyliusFedexPlugin\Factory\ShipmentPayloadFactoryInterface;

final class ShippingLabelFetcherTest extends TestCase
{
    public function testCreateShipmentSuccess(): void
    {
        $requestStack = new RequestStack();
        $client = $this->createMock(FedexClientInterface::class);
        $payloadFactory = $this->createMock(ShipmentPayloadFactoryInterface::class);

        $gateway = $this->createMock(ShippingGatewayInterface::class);
        $gateway->method('getConfig')->willReturn([
            'client_id' => 'test_client_id',
            'client_secret' => 'test_client_secret',
            'environment' => 'sandbox',
            'label_image_type' => 'PDF',
        ]);

        $shipment = $this->createMock(ShipmentInterface::class);

        $payloadFactory->method('createNew')->willReturn(['payload' => 'dummy']);

        $client->method('createShipment')->willReturn([
            'output' => [
                'transactionShipments' => [
                    [
                        'masterTrackingNumber' => '123456789012',
                        'pieceResponses' => [
                            [
                                'packageDocuments' => [
                                    [
                                        'contentType' => 'application/pdf',
                                        'encodedLabel' => base64_encode('LABEL_PDF_BINARY_CONTENT'),
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $fetcher = new ShippingLabelFetcher($requestStack, $client, $payloadFactory, 'KG');
        $fetcher->createShipment($gateway, $shipment);

        $this->assertTrue($fetcher->isSuccess());
        $this->assertEquals('123456789012', $fetcher->getTrackingNumber());
        $this->assertEquals('LABEL_PDF_BINARY_CONTENT', $fetcher->getLabelContent());
        $this->assertEquals('pdf', $fetcher->getLabelExtension());
    }
}
