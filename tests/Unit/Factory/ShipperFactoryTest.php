<?php

declare(strict_types=1);

namespace Tests\Waaz\SyliusFedexPlugin\Unit\Factory;

use BitBag\SyliusShippingExportPlugin\Entity\ShippingGatewayInterface;
use PHPUnit\Framework\TestCase;
use Waaz\SyliusFedexPlugin\Factory\ShipperFactory;

final class ShipperFactoryTest extends TestCase
{
    public function testCreateNew(): void
    {
        $gateway = $this->createMock(ShippingGatewayInterface::class);
        $gateway->method('getConfig')->willReturn([
            'shipper_person_name' => 'John Doe',
            'shipper_company_name' => 'ACME Corp',
            'shipper_phone_number' => '+33123456789',
            'shipper_email_address' => 'contact@acme.com',
            'shipper_address1' => '10 Rue de la Paix',
            'shipper_address2' => 'Batiment B',
            'shipper_city' => 'Paris',
            'shipper_postal_code' => '75002',
            'shipper_country_code' => 'FR',
        ]);

        $factory = new ShipperFactory();
        $result = $factory->createNew($gateway);

        $this->assertEquals([
            'personName' => 'John Doe',
            'companyName' => 'ACME Corp',
            'phoneNumber' => '+33123456789',
            'emailAddress' => 'contact@acme.com',
        ], $result['contact']);

        $this->assertEquals([
            'streetLines' => ['10 Rue de la Paix', 'Batiment B'],
            'city' => 'Paris',
            'postalCode' => '75002',
            'countryCode' => 'FR',
        ], $result['address']);
    }
}
