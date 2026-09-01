<?php

declare(strict_types=1);

namespace Waaz\SyliusFedexPlugin\Api;

use BitBag\SyliusShippingExportPlugin\Entity\ShippingGatewayInterface;
use Sylius\Component\Core\Model\ShipmentInterface;
use Sylius\Component\Order\Model\OrderInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Waaz\SyliusFedexPlugin\Factory\ShipmentPayloadFactoryInterface;
use Webmozart\Assert\Assert;

class ShippingLabelFetcher implements ShippingLabelFetcherInterface
{
    /** @var array<string, mixed>|null */
    private ?array $response = null;

    private ?string $labelContent = null;

    private string $labelExtension = 'pdf';

    private ?string $trackingNumber = null;

    private bool $success = false;

    public function __construct(
        private RequestStack $requestStack,
        private FedexClientInterface $client,
        private ShipmentPayloadFactoryInterface $shipmentPayloadFactory,
        private string $weightUnit = 'KG',
    ) {
    }

    public function createShipment(ShippingGatewayInterface $shippingGateway, ShipmentInterface $shipment): void
    {
        $this->response = null;
        $this->labelContent = null;
        $this->trackingNumber = null;
        $this->success = false;

        $config = $shippingGateway->getConfig();

        $clientId = trim((string) ($config['client_id'] ?? ''));
        $clientSecret = trim((string) ($config['client_secret'] ?? ''));
        $environment = (string) ($config['environment'] ?? 'sandbox');
        $sandbox = $environment === 'sandbox';
        $labelImageType = (string) ($config['label_image_type'] ?? 'PDF');
        $this->labelExtension = strtolower($labelImageType);

        try {
            $payload = $this->shipmentPayloadFactory->createNew(
                $shippingGateway,
                $shipment,
                $this->weightUnit,
            );

            $this->response = $this->client->createShipment($payload, $clientId, $clientSecret, $sandbox);
            $this->extractLabelAndTracking();
            $this->success = true;

            $this->addFlash('success', 'bitbag.ui.shipment_data_has_been_exported');
        } catch (\Throwable $exception) {
            $this->success = false;
            $order = $shipment->getOrder();
            $number = ($order instanceof OrderInterface) ? (string) $order->getNumber() : 'N/A';

            $this->addFlash(
                'error',
                sprintf('FedEx error for order #%s: %s', $number, $exception->getMessage()),
            );
        }
    }

    public function getLabelContent(): ?string
    {
        return $this->labelContent;
    }

    public function getLabelExtension(): string
    {
        return $this->labelExtension;
    }

    public function getTrackingNumber(): ?string
    {
        return $this->trackingNumber;
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    private function extractLabelAndTracking(): void
    {
        Assert::isArray($this->response, 'FedEx response is empty.');

        $transactionShipments = $this->response['output']['transactionShipments'] ?? [];
        if ($transactionShipments === []) {
            return;
        }

        $shipmentData = $transactionShipments[0] ?? [];
        $this->trackingNumber = (string) ($shipmentData['masterTrackingNumber'] ?? '');

        $pieceResponses = $shipmentData['pieceResponses'] ?? [];
        foreach ($pieceResponses as $piece) {
            $packageDocuments = $piece['packageDocuments'] ?? [];
            foreach ($packageDocuments as $doc) {
                $encodedLabel = $doc['encodedLabel'] ?? null;
                if (\is_string($encodedLabel) && $encodedLabel !== '') {
                    $decoded = base64_decode($encodedLabel, true);
                    if ($decoded !== false) {
                        $this->labelContent = $decoded;
                        $docType = (string) ($doc['contentType'] ?? '');
                        if (stripos($docType, 'png') !== false) {
                            $this->labelExtension = 'png';
                        } else {
                            $this->labelExtension = 'pdf';
                        }

                        return;
                    }
                }
            }
        }
    }

    private function addFlash(string $type, string $message): void
    {
        try {
            $session = $this->requestStack->getSession();
            if (method_exists($session, 'getFlashBag')) {
                $session->getFlashBag()->add($type, $message);
            } elseif ($session instanceof SessionInterface) {
                $flashBag = $session->getBag('flashes');
                if ($flashBag instanceof FlashBagInterface) {
                    $flashBag->add($type, $message);
                }
            }
        } catch (\Throwable) {
            // Session not active or in CLI
        }
    }
}
