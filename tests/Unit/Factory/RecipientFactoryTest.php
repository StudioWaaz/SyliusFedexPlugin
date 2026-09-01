<?php

declare(strict_types=1);

namespace Tests\Waaz\SyliusFedexPlugin\Unit\Factory;

use PHPUnit\Framework\TestCase;
use Sylius\Component\Core\Model\AddressInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\ShipmentInterface;
use Waaz\SyliusFedexPlugin\Factory\RecipientFactory;

final class RecipientFactoryTest extends TestCase
{
    public function testCreateNew(): void
    {
        $address = $this->createMock(AddressInterface::class);
        $address->method('getFirstName')->willReturn('Jane');
        $address->method('getLastName')->willReturn('Smith');
        $address->method('getCompany')->willReturn('Customer LLC');
        $address->method('getPhoneNumber')->willReturn('+33612345678');
        $address->method('getStreet')->willReturn('20 Avenue des Champs-Elysees');
        $address->method('getCity')->willReturn('Paris');
        $address->method('getPostcode')->willReturn('75008');
        $address->method('getCountryCode')->willReturn('FR');

        $customer = $this->createMock(CustomerInterface::class);
        $customer->method('getEmail')->willReturn('jane@example.com');

        $order = $this->createMock(OrderInterface::class);
        $order->method('getShippingAddress')->willReturn($address);
        $order->method('getCustomer')->willReturn($customer);

        $shipment = $this->createMock(ShipmentInterface::class);
        $shipment->method('getOrder')->willReturn($order);

        $factory = new RecipientFactory();
        $result = $factory->createNew($shipment);

        $this->assertEquals([
            'personName' => 'Jane Smith',
            'companyName' => 'Customer LLC',
            'phoneNumber' => '+33612345678',
            'emailAddress' => 'jane@example.com',
        ], $result['contact']);

        $this->assertEquals([
            'streetLines' => ['20 Avenue des Champs-Elysees'],
            'city' => 'Paris',
            'postalCode' => '75008',
            'countryCode' => 'FR',
        ], $result['address']);
    }
}
