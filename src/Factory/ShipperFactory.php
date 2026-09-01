<?php

declare(strict_types=1);

namespace Waaz\SyliusFedexPlugin\Factory;

use BitBag\SyliusShippingExportPlugin\Entity\ShippingGatewayInterface;

final class ShipperFactory implements ShipperFactoryInterface
{
    public function createNew(ShippingGatewayInterface $shippingGateway): array
    {
        $config = $shippingGateway->getConfig();

        $personName = (string) ($config['shipper_person_name'] ?? '');
        $companyName = (string) ($config['shipper_company_name'] ?? '');
        $phoneNumber = (string) ($config['shipper_phone_number'] ?? '');
        $emailAddress = (string) ($config['shipper_email_address'] ?? '');

        $address1 = (string) ($config['shipper_address1'] ?? '');
        $address2 = (string) ($config['shipper_address2'] ?? '');
        $city = (string) ($config['shipper_city'] ?? '');
        $postalCode = (string) ($config['shipper_postal_code'] ?? '');
        $countryCode = (string) ($config['shipper_country_code'] ?? 'FR');

        $streetLines = array_values(array_filter([$address1, $address2]));

        $contact = array_filter([
            'personName' => $personName !== '' ? $personName : null,
            'companyName' => $companyName !== '' ? $companyName : null,
            'phoneNumber' => $phoneNumber !== '' ? $phoneNumber : null,
            'emailAddress' => $emailAddress !== '' ? $emailAddress : null,
        ]);

        return [
            'contact' => $contact,
            'address' => [
                'streetLines' => $streetLines,
                'city' => $city,
                'postalCode' => $postalCode,
                'countryCode' => strtoupper($countryCode),
            ],
        ];
    }
}
