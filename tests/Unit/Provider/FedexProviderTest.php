<?php

declare(strict_types=1);

namespace Tests\Waaz\SyliusFedexPlugin\Unit\Provider;

use PHPUnit\Framework\TestCase;
use Setono\SyliusPickupPointPlugin\Model\PickupPointCode;
use Sylius\Component\Core\Model\AddressInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Waaz\SyliusFedexPlugin\Api\FedexClientInterface;
use Waaz\SyliusFedexPlugin\Provider\FedexProvider;

final class FedexProviderTest extends TestCase
{
    public function testFindPickupPoints(): void
    {
        $client = $this->createMock(FedexClientInterface::class);
        $client->method('searchLocations')->willReturn([
            [
                'locationId' => 'FXO123',
                'locationContactAndAddress' => [
                    'contact' => ['companyName' => 'FedEx Office Paris'],
                    'address' => [
                        'streetLines' => ['15 Boulevard Saint-Michel'],
                        'city' => 'Paris',
                        'postalCode' => '75005',
                        'countryCode' => 'FR',
                        'geoCoordinates' => ['latitude' => 48.851, 'longitude' => 2.344],
                    ],
                ],
            ],
        ]);

        $address = $this->createMock(AddressInterface::class);
        $address->method('getPostcode')->willReturn('75005');
        $address->method('getCountryCode')->willReturn('FR');
        $address->method('getCity')->willReturn('Paris');
        $address->method('getStreet')->willReturn('10 Rue Soufflot');

        $order = $this->createMock(OrderInterface::class);
        $order->method('getShippingAddress')->willReturn($address);

        $provider = new FedexProvider($client, 'key', 'secret', true);
        $pickupPoints = iterator_to_array($provider->findPickupPoints($order));

        $this->assertCount(1, $pickupPoints);
        $pickupPoint = $pickupPoints[0];
        $this->assertEquals('FedEx Office Paris', $pickupPoint->getName());
        $this->assertEquals('15 Boulevard Saint-Michel', $pickupPoint->getAddress());
        $this->assertEquals('75005', $pickupPoint->getZipCode());
        $this->assertEquals('Paris', $pickupPoint->getCity());
        $this->assertEquals('FR', $pickupPoint->getCountry());
        $this->assertEquals(48.851, $pickupPoint->getLatitude());
        $this->assertEquals(2.344, $pickupPoint->getLongitude());
    }

    public function testFindPickupPointByCode(): void
    {
        $client = $this->createMock(FedexClientInterface::class);
        $client->method('searchLocations')->willReturn([
            [
                'locationId' => 'FXO123',
                'locationContactAndAddress' => [
                    'contact' => ['companyName' => 'FedEx Office Paris'],
                    'address' => [
                        'streetLines' => ['15 Boulevard Saint-Michel'],
                        'city' => 'Paris',
                        'postalCode' => '75005',
                        'countryCode' => 'FR',
                    ],
                ],
            ],
        ]);

        $provider = new FedexProvider($client, 'key', 'secret', true);
        $code = new PickupPointCode('FXO123###75005###Paris', 'fedex', 'FR');

        $pickupPoint = $provider->findPickupPoint($code);
        $this->assertNotNull($pickupPoint);
        $this->assertEquals('FedEx Office Paris', $pickupPoint->getName());
    }
}
