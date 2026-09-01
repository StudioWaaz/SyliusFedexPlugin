<?php

declare(strict_types=1);

namespace Waaz\SyliusFedexPlugin\Factory;

use BitBag\SyliusShippingExportPlugin\Entity\ShippingGatewayInterface;
use Sylius\Component\Core\Model\ShipmentInterface;

final class ShipmentPayloadFactory implements ShipmentPayloadFactoryInterface
{
    public function __construct(
        private ShipperFactoryInterface $shipperFactory,
        private RecipientFactoryInterface $recipientFactory,
        private PackageLineItemsFactoryInterface $packageLineItemsFactory,
    ) {
    }

    public function createNew(
        ShippingGatewayInterface $shippingGateway,
        ShipmentInterface $shipment,
        string $weightUnit = 'KG',
    ): array {
        $config = $shippingGateway->getConfig();

        $accountNumber = (string) ($config['account_number'] ?? '');
        $serviceType = (string) ($config['service_type'] ?? 'FEDEX_GROUND');
        $dropoffType = (string) ($config['dropoff_type'] ?? 'REGULAR_PICKUP');
        $packagingType = (string) ($config['packaging_type'] ?? 'YOUR_PACKAGING');
        $labelImageType = (string) ($config['label_image_type'] ?? 'PDF');
        $labelStockType = (string) ($config['label_stock_type'] ?? 'PAPER_4X6');

        $shipper = $this->shipperFactory->createNew($shippingGateway);
        $recipient = $this->recipientFactory->createNew($shipment);
        $packageLineItems = $this->packageLineItemsFactory->createNew($shipment, $weightUnit);

        return [
            'labelResponseOptions' => 'LABEL',
            'requestedShipment' => [
                'shipper' => $shipper,
                'recipients' => [$recipient],
                'shipDatestamp' => (new \DateTime())->format('Y-m-d'),
                'serviceType' => $serviceType,
                'packagingType' => $packagingType,
                'pickupType' => $dropoffType,
                'shippingChargesPayment' => [
                    'paymentType' => 'SENDER',
                    'payor' => [
                        'responsibleParty' => [
                            'accountNumber' => [
                                'value' => $accountNumber,
                            ],
                        ],
                    ],
                ],
                'labelSpecification' => [
                    'labelFormatType' => 'COMMON2D',
                    'imageType' => strtoupper($labelImageType),
                    'labelStockType' => $labelStockType,
                ],
                'requestedPackageLineItems' => $packageLineItems,
            ],
            'accountNumber' => [
                'value' => $accountNumber,
            ],
        ];
    }
}
