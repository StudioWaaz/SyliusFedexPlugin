<?php

declare(strict_types=1);

namespace Waaz\SyliusFedexPlugin\Provider;

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
            if (null !== $gateway) {
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
            if (null !== $gateway) {
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
            $locId = (string) ($item['locationId'] ?? $item['locationCode'] ?? '');
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
        $contactAndAddress = $location['contactAndAddress'] ?? $location['locationContactAndAddress'] ?? [];
        $address = $contactAndAddress['address'] ?? [];
        $contact = $contactAndAddress['contact'] ?? [];

        $locationId = (string) ($location['locationId'] ?? $location['locationCode'] ?? 'UNKNOWN');
        $postalCode = (string) ($address['postalCode'] ?? '');
        $city = (string) ($address['city'] ?? '');
        $country = (string) ($address['countryCode'] ?? $countryCode);

        $streetLines = $address['streetLines'] ?? [];
        $street = \is_array($streetLines) ? implode(', ', $streetLines) : (string) $streetLines;

        $name = (string) ($contact['companyName'] ?? $contact['personName'] ?? $location['locationDetail'] ?? 'FedEx Location');

        $pickupPoint = new PickupPoint();
        $pickupId = $locationId . '###' . $postalCode . '###' . $city;
        $pickupPointCode = new PickupPointCode($pickupId, $this->getCode(), $country);

        $pickupPoint->setCode($pickupPointCode);
        $pickupPoint->setName($name);
        $pickupPoint->setAddress($street);
        $pickupPoint->setZipCode($postalCode);
        $pickupPoint->setCity($city);
        $pickupPoint->setCountry($country);

        $coordinates = $address['geoCoordinates'] ?? $address['geographicCoordinates'] ?? $location['geoPositionalCoordinates'] ?? [];
        if (isset($coordinates['latitude'], $coordinates['longitude'])) {
            $pickupPoint->setLatitude((float) $coordinates['latitude']);
            $pickupPoint->setLongitude((float) $coordinates['longitude']);
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
