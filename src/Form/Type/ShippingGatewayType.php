<?php

declare(strict_types=1);

namespace Waaz\SyliusFedexPlugin\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

final class ShippingGatewayType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('client_id', TextType::class, [
                'label' => 'waaz.ui.fedex_client_id',
                'constraints' => [
                    new NotBlank(['groups' => ['bitbag']]),
                ],
            ])
            ->add('client_secret', PasswordType::class, [
                'label' => 'waaz.ui.fedex_client_secret',
                'always_empty' => false,
                'constraints' => [
                    new NotBlank(['groups' => ['bitbag']]),
                ],
            ])
            ->add('account_number', TextType::class, [
                'label' => 'waaz.ui.fedex_account_number',
                'constraints' => [
                    new NotBlank(['groups' => ['bitbag']]),
                ],
            ])
            ->add('environment', ChoiceType::class, [
                'label' => 'waaz.ui.fedex_environment',
                'choices' => [
                    'waaz.ui.fedex.sandbox' => 'sandbox',
                    'waaz.ui.fedex.production' => 'production',
                ],
                'data' => 'sandbox',
            ])
            ->add('service_type', ChoiceType::class, [
                'label' => 'waaz.ui.fedex_service_type',
                'choices' => [
                    'waaz.ui.fedex.service.fedex_ground' => 'FEDEX_GROUND',
                    'waaz.ui.fedex.service.fedex_express_saver' => 'FEDEX_EXPRESS_SAVER',
                    'waaz.ui.fedex.service.standard_overnight' => 'STANDARD_OVERNIGHT',
                    'waaz.ui.fedex.service.priority_overnight' => 'PRIORITY_OVERNIGHT',
                    'waaz.ui.fedex.service.first_overnight' => 'FIRST_OVERNIGHT',
                    'waaz.ui.fedex.service.fedex_2_day' => 'FEDEX_2_DAY',
                    'waaz.ui.fedex.service.international_economy' => 'INTERNATIONAL_ECONOMY',
                    'waaz.ui.fedex.service.international_priority' => 'INTERNATIONAL_PRIORITY',
                    'waaz.ui.fedex.service.europe_first_international_priority' => 'EUROPE_FIRST_INTERNATIONAL_PRIORITY',
                ],
                'data' => 'FEDEX_GROUND',
            ])
            ->add('dropoff_type', ChoiceType::class, [
                'label' => 'waaz.ui.fedex_dropoff_type',
                'choices' => [
                    'waaz.ui.fedex.dropoff.use_scheduled_pickup' => 'USE_SCHEDULED_PICKUP',
                    'waaz.ui.fedex.dropoff.dropoff_at_fedex_location' => 'DROPOFF_AT_FEDEX_LOCATION',
                    'waaz.ui.fedex.dropoff.contact_fedex_to_schedule' => 'CONTACT_FEDEX_TO_SCHEDULE',
                ],
                'data' => 'USE_SCHEDULED_PICKUP',
            ])
            ->add('packaging_type', ChoiceType::class, [
                'label' => 'waaz.ui.fedex_packaging_type',
                'choices' => [
                    'waaz.ui.fedex.packaging.your_packaging' => 'YOUR_PACKAGING',
                    'waaz.ui.fedex.packaging.fedex_box' => 'FEDEX_BOX',
                    'waaz.ui.fedex.packaging.fedex_envelope' => 'FEDEX_ENVELOPE',
                    'waaz.ui.fedex.packaging.fedex_pak' => 'FEDEX_PAK',
                    'waaz.ui.fedex.packaging.fedex_tube' => 'FEDEX_TUBE',
                ],
                'data' => 'YOUR_PACKAGING',
            ])
            ->add('label_image_type', ChoiceType::class, [
                'label' => 'waaz.ui.fedex_label_image_type',
                'choices' => [
                    'PDF' => 'PDF',
                    'PNG' => 'PNG',
                ],
                'data' => 'PDF',
            ])
            ->add('label_stock_type', ChoiceType::class, [
                'label' => 'waaz.ui.fedex_label_stock_type',
                'choices' => [
                    'PAPER_4X6' => 'PAPER_4X6',
                    'PAPER_4X8' => 'PAPER_4X8',
                    'PAPER_7X4.75' => 'PAPER_7X4.75',
                    'PAPER_8.5X11_TOP_HALF_LABEL' => 'PAPER_8.5X11_TOP_HALF_LABEL',
                    'PAPER_8.5X11_BOTTOM_HALF_LABEL' => 'PAPER_8.5X11_BOTTOM_HALF_LABEL',
                ],
                'data' => 'PAPER_4X6',
            ])
            ->add('shipper_person_name', TextType::class, [
                'label' => 'waaz.ui.fedex_shipper_person_name',
                'constraints' => [
                    new NotBlank(['groups' => ['bitbag']]),
                ],
            ])
            ->add('shipper_company_name', TextType::class, [
                'label' => 'waaz.ui.fedex_shipper_company_name',
                'required' => false,
            ])
            ->add('shipper_phone_number', TextType::class, [
                'label' => 'waaz.ui.fedex_shipper_phone_number',
                'constraints' => [
                    new NotBlank(['groups' => ['bitbag']]),
                ],
            ])
            ->add('shipper_email_address', TextType::class, [
                'label' => 'waaz.ui.fedex_shipper_email_address',
                'required' => false,
            ])
            ->add('shipper_address1', TextType::class, [
                'label' => 'waaz.ui.fedex_shipper_address1',
                'constraints' => [
                    new NotBlank(['groups' => ['bitbag']]),
                ],
            ])
            ->add('shipper_address2', TextType::class, [
                'label' => 'waaz.ui.fedex_shipper_address2',
                'required' => false,
            ])
            ->add('shipper_city', TextType::class, [
                'label' => 'waaz.ui.fedex_shipper_city',
                'constraints' => [
                    new NotBlank(['groups' => ['bitbag']]),
                ],
            ])
            ->add('shipper_postal_code', TextType::class, [
                'label' => 'waaz.ui.fedex_shipper_postal_code',
                'constraints' => [
                    new NotBlank(['groups' => ['bitbag']]),
                ],
            ])
            ->add('shipper_country_code', CountryType::class, [
                'label' => 'waaz.ui.fedex_shipper_country_code',
                'data' => 'FR',
                'constraints' => [
                    new NotBlank(['groups' => ['bitbag']]),
                ],
            ])
        ;
    }
}
