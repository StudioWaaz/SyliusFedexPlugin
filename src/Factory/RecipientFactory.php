<?php

declare(strict_types=1);

namespace Waaz\SyliusFedexPlugin\Factory;

use Sylius\Component\Core\Model\AddressInterface;
use Sylius\Component\Core\Model\ShipmentInterface;
use Webmozart\Assert\Assert;

final class RecipientFactory implements RecipientFactoryInterface
{
    public function createNew(ShipmentInterface $shipment): array
    {
        $order = $shipment->getOrder();
        Assert::notNull($order, 'Shipment order cannot be null.');

        $shippingAddress = $order->getShippingAddress();
        Assert::isInstanceOf($shippingAddress, AddressInterface::class, 'Order shipping address must be defined.');

        $personName = trim((string) $shippingAddress->getFirstName() . ' ' . (string) $shippingAddress->getLastName());
        $companyName = (string) $shippingAddress->getCompany();
        $phoneNumber = (string) $shippingAddress->getPhoneNumber();
        $customer = $order->getCustomer();
        $emailAddress = $customer !== null ? (string) $customer->getEmail() : '';

        $streetLines = array_values(array_filter([
            (string) $shippingAddress->getStreet(),
        ]));

        $contact = array_filter([
            'personName' => $personName !== '' ? $personName : null,
            'companyName' => $companyName !== '' ? $companyName : null,
            'phoneNumber' => $phoneNumber !== '' ? $phoneNumber : '0000000000',
            'emailAddress' => $emailAddress !== '' ? $emailAddress : null,
        ]);

        return [
            'contact' => $contact,
            'address' => [
                'streetLines' => $streetLines,
                'city' => (string) $shippingAddress->getCity(),
                'postalCode' => (string) $shippingAddress->getPostcode(),
                'countryCode' => strtoupper((string) $shippingAddress->getCountryCode()),
            ],
        ];
    }
}
