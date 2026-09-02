<?php

declare(strict_types=1);

namespace Waaz\SyliusFedexPlugin\Provider;

use BitBag\SyliusShippingExportPlugin\Entity\ShippingGatewayInterface;
use Setono\SyliusPickupPointPlugin\Exception\TimeoutException;
use Setono\SyliusPickupPointPlugin\Model\PickupPoint;
use Setono\SyliusPickupPointPlugin\Model\PickupPointCode;
use Setono\SyliusPickupPointPlugin\Model\PickupPointInterface;
use Setono\SyliusPickupPointPlugin\Provider\Provider;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Waaz\SyliusFedexPlugin\Api\Exception\FedexApiException;
use Waaz\SyliusFedexPlugin\Api\FedexClientInterface;
use Webmozart\Assert\Assert;

final class FedexProvider extends Provider
{
    public function __construct(
        private FedexClientInterface $client,
        private RepositoryInterface $shippingGatewayRepository,
        private ?string $clientId = null,
        private ?string $clientSecret = null,
        private bool $sandbox = true,
    ) {
    }

    public function findPickupPoints(OrderInterface $order): iterable
    {
        $shippingAddress = $order->getShippingAddress();
        if (null === $shippingAddress) {
            return [];
        }

        $postcode = $shippingAddress->getPostcode();
        $countryCode = $shippingAddress->getCountryCode();
        $city = $shippingAddress->getCity();
        $street = $shippingAddress->getStreet();

        if (null === $postcode || null === $countryCode) {
            return [];
        }

        $clientId = $this->clientId;
        $clientSecret = $this->clientSecret;
        $sandbox = $this->sandbox;

        if (null === $clientId || '' === $clientId || null === $clientSecret || '' === $clientSecret) {
            $gateway = $this->shippingGatewayRepository->findOneBy(['code' => 'fedex']);
            if ($gateway instanceof ShippingGatewayInterface) {
                $config = $gateway->getConfig();
                $clientId = trim((string) ($config['client_id'] ?? ''));
                $clientSecret = trim((string) ($config['client_secret'] ?? ''));
                $environment = (string) ($config['environment'] ?? 'sandbox');
                $sandbox = $environment === 'sandbox';
            }
        }

        try {
            $locations = $this->client->searchLocations(
                postalCode: $postcode,
                countryCode: $countryCode,
                city: $city,
                address: $street,
                radiusKm: 50,
                clientId: $clientId,
                clientSecret: $clientSecret,
                sandbox: $sandbox,
            );
        } catch (FedexApiException $e) {
            throw new TimeoutException($e);
        }

        foreach ($locations as $item) {
            yield $this->transform($item, $countryCode);
        }
    }

    public function findPickupPoint(PickupPointCode $code): ?PickupPointInterface
    {
        $pickupId = $code->getIdPart();
        $data = \explode('###', $pickupId);
        Assert::greaterThanEq(\count($data), 2, 'FedEx Pickup ID is not correct.');
        $locationId = $data[0];
        $postcode = $data[1];
        $countryCode = $code->getCountryPart();

        $clientId = $this->clientId;
        $clientSecret = $this->clientSecret;
        $sandbox = $this->sandbox;

        if (null === $clientId || '' === $clientId || null === $clientSecret || '' === $clientSecret) {
            $gateway = $this->shippingGatewayRepository->findOneBy(['code' => 'fedex']);
            if ($gateway instanceof ShippingGatewayInterface) {
                $config = $gateway->getConfig();
                $clientId = trim((string) ($config['client_id'] ?? ''));
                $clientSecret = trim((string) ($config['client_secret'] ?? ''));
                $environment = (string) ($config['environment'] ?? 'sandbox');
                $sandbox = $environment === 'sandbox';
            }
        }

        try {
            $locations = $this->client->searchLocations(
                postalCode: $postcode,
                countryCode: $countryCode,
                radiusKm: 50,
                clientId: $clientId,
                clientSecret: $clientSecret,
                sandbox: $sandbox,
            );
        } catch (FedexApiException $e) {
            return null;
        }

        foreach ($locations as $item) {
            /** @var mixed $locIdVal */
            $locIdVal = $item['locationId'] ?? $item['locationCode'] ?? '';
            $locId = \is_string($locIdVal) || \is_numeric($locIdVal) ? (string) $locIdVal : '';
            if ($locId === $locationId) {
                return $this->transform($item, $countryCode);
            }
        }

        return null;
    }

    public function findAllPickupPoints(): iterable
    {
        return [];
    }

    /**
     * @param array<string, mixed> $location
     */
    private function transform(array $location, string $countryCode): PickupPoint
    {
        /** @var mixed $contactAndAddress */
        $contactAndAddress = $location['contactAndAddress'] ?? $location['locationContactAndAddress'] ?? [];
        if (!\is_array($contactAndAddress)) {
            $contactAndAddress = [];
        }
        /** @var mixed $address */
        $address = $contactAndAddress['address'] ?? [];
        if (!\is_array($address)) {
            $address = [];
        }
        /** @var mixed $contact */
        $contact = $contactAndAddress['contact'] ?? [];
        if (!\is_array($contact)) {
            $contact = [];
        }

        /** @var mixed $locIdVal */
        $locIdVal = $location['locationId'] ?? $location['locationCode'] ?? 'UNKNOWN';
        $locationId = \is_string($locIdVal) || \is_numeric($locIdVal) ? (string) $locIdVal : 'UNKNOWN';

        /** @var mixed $postalCodeVal */
        $postalCodeVal = $address['postalCode'] ?? '';
        $postalCode = \is_string($postalCodeVal) || \is_numeric($postalCodeVal) ? (string) $postalCodeVal : '';

        /** @var mixed $cityVal */
        $cityVal = $address['city'] ?? '';
        $city = \is_string($cityVal) || \is_numeric($cityVal) ? (string) $cityVal : '';

        /** @var mixed $countryVal */
        $countryVal = $address['countryCode'] ?? $countryCode;
        $country = \is_string($countryVal) || \is_numeric($countryVal) ? (string) $countryVal : $countryCode;

        /** @var mixed $streetLines */
        $streetLines = $address['streetLines'] ?? [];
        /** @var array<array-key, string> $streetLinesArray */
        $streetLinesArray = [];
        if (\is_array($streetLines)) {
            /** @var mixed $line */
            foreach ($streetLines as $line) {
                if (\is_string($line) || \is_numeric($line)) {
                    $streetLinesArray[] = (string) $line;
                }
            }
        }
        $street = \is_array($streetLines) ? implode(', ', $streetLinesArray) : (\is_string($streetLines) || \is_numeric($streetLines) ? (string) $streetLines : '');

        /** @var mixed $companyNameVal */
        $companyNameVal = $contact['companyName'] ?? null;
        /** @var mixed $personNameVal */
        $personNameVal = $contact['personName'] ?? null;
        /** @var mixed $locationDetailVal */
        $locationDetailVal = $location['locationDetail'] ?? null;

        $name = 'FedEx Location';
        if (\is_string($companyNameVal) && $companyNameVal !== '') {
            $name = $companyNameVal;
        } elseif (\is_string($personNameVal) && $personNameVal !== '') {
            $name = $personNameVal;
        } elseif (\is_string($locationDetailVal) && $locationDetailVal !== '') {
            $name = $locationDetailVal;
        }

        $pickupPoint = new PickupPoint();
        $pickupId = $locationId . '###' . $postalCode . '###' . $city;
        $pickupPointCode = new PickupPointCode($pickupId, $this->getCode(), $country);

        $pickupPoint->setCode($pickupPointCode);
        $pickupPoint->setName($name);
        $pickupPoint->setAddress($street);
        $pickupPoint->setZipCode($postalCode);
        $pickupPoint->setCity($city);
        $pickupPoint->setCountry($country);

        /** @var mixed $coordinates */
        $coordinates = $address['geoCoordinates'] ?? $address['geographicCoordinates'] ?? $location['geoPositionalCoordinates'] ?? [];
        if (\is_array($coordinates) && isset($coordinates['latitude'], $coordinates['longitude'])) {
            /** @var mixed $latitudeVal */
            $latitudeVal = $coordinates['latitude'];
            /** @var mixed $longitudeVal */
            $longitudeVal = $coordinates['longitude'];
            if ((\is_float($latitudeVal) || \is_numeric($latitudeVal) || \is_string($latitudeVal)) &&
                (\is_float($longitudeVal) || \is_numeric($longitudeVal) || \is_string($longitudeVal))) {
                $pickupPoint->setLatitude((float) $latitudeVal);
                $pickupPoint->setLongitude((float) $longitudeVal);
            }
        }

        return $pickupPoint;
    }

    public function getCode(): string
    {
        return 'fedex';
    }

    public function getName(): string
    {
        return 'FedEx';
    }
}
