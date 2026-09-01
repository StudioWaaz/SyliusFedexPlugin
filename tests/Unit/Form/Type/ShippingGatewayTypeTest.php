<?php

declare(strict_types=1);

namespace Tests\Waaz\SyliusFedexPlugin\Unit\Form\Type;

use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Validator\Validation;
use Waaz\SyliusFedexPlugin\Form\Type\ShippingGatewayType;

final class ShippingGatewayTypeTest extends TypeTestCase
{
    protected function getExtensions(): array
    {
        $validator = Validation::createValidator();

        return [
            new ValidatorExtension($validator),
        ];
    }

    public function testSubmitValidData(): void
    {
        $formData = [
            'client_id' => 'l7xx_sample_key',
            'client_secret' => 'sample_secret',
            'account_number' => '123456789',
            'environment' => 'sandbox',
            'service_type' => 'FEDEX_GROUND',
            'dropoff_type' => 'USE_SCHEDULED_PICKUP',
            'packaging_type' => 'YOUR_PACKAGING',
            'label_image_type' => 'PDF',
            'label_stock_type' => 'PAPER_4X6',
            'shipper_person_name' => 'John Doe',
            'shipper_company_name' => 'ACME',
            'shipper_phone_number' => '+33123456789',
            'shipper_email_address' => 'john@acme.com',
            'shipper_address1' => '10 Rue de la Paix',
            'shipper_address2' => '',
            'shipper_city' => 'Paris',
            'shipper_postal_code' => '75002',
            'shipper_country_code' => 'FR',
        ];

        $form = $this->factory->create(ShippingGatewayType::class);
        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertTrue($form->isValid());
        $this->assertEquals($formData, $form->getData());
    }
}
