<?php

declare(strict_types=1);

namespace Tests\Waaz\SyliusFedexPlugin\Unit\Provider;

use PHPUnit\Framework\TestCase;
use Setono\SyliusPickupPointPlugin\Model\PickupPointCode;
use Sylius\Component\Core\Model\AddressInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
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

        $gatewayRepository = $this->createMock(RepositoryInterface::class);

        $provider = new FedexProvider($client, $gatewayRepository, 'key', 'secret', true);
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

        $gatewayRepository = $this->createMock(RepositoryInterface::class);

        $provider = new FedexProvider($client, $gatewayRepository, 'key', 'secret', true);
        $code = new PickupPointCode('FXO123###75005###Paris', 'fedex', 'FR');

        $pickupPoint = $provider->findPickupPoint($code);
        $this->assertNotNull($pickupPoint);
        $this->assertEquals('FedEx Office Paris', $pickupPoint->getName());
    }

    public function testFindPickupPointsWithGatewayConfig(): void
    {
        $client = $this->createMock(FedexClientInterface::class);
        $client->expects($this->once())
            ->method('searchLocations')
            ->with(
                $this->equalTo('75005'),
                $this->equalTo('FR'),
                $this->equalTo('Paris'),
                $this->equalTo('10 Rue Soufflot'),
                $this->equalTo(50),
                $this->equalTo('gateway_id'),
                $this->equalTo('gateway_secret'),
                $this->equalTo(true)
            )
            ->willReturn([
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

        $address = $this->createMock(AddressInterface::class);
        $address->method('getPostcode')->willReturn('75005');
        $address->method('getCountryCode')->willReturn('FR');
        $address->method('getCity')->willReturn('Paris');
        $address->method('getStreet')->willReturn('10 Rue Soufflot');

        $order = $this->createMock(OrderInterface::class);
        $order->method('getShippingAddress')->willReturn($address);

        $gateway = $this->createMock(\BitBag\SyliusShippingExportPlugin\Entity\ShippingGatewayInterface::class);
        $gateway->method('getConfig')->willReturn([
            'client_id' => 'gateway_id',
            'client_secret' => 'gateway_secret',
            'environment' => 'sandbox',
        ]);

        $gatewayRepository = $this->createMock(RepositoryInterface::class);
        $gatewayRepository->method('findOneBy')->with(['code' => 'fedex'])->willReturn($gateway);

        $provider = new FedexProvider($client, $gatewayRepository);
        $pickupPoints = iterator_to_array($provider->findPickupPoints($order));

        $this->assertCount(1, $pickupPoints);
    }
}
